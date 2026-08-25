/**
 * سرور سیگنالینگ کلاس آنلاین Nexa
 * -----------------------------------------------------------
 * این سرور خودش هیچ ویدیو/صدایی رو پردازش نمی‌کنه (اونا مستقیم بین
 * دستگاه‌ها با WebRTC ردوبدل می‌شن)؛ فقط پیام‌های سیگنالینگ (offer/answer/
 * ice-candidate)، رویدادهای تخته‌ی مشترک، و دست‌بالا/اجازه‌ی میکروفون رو
 * بین شرکت‌کننده‌های یک room (= یک جلسه‌ی کلاس آنلاین) رله می‌کنه.
 *
 * هیچ دیتابیسی نداره. برای تایید هر کاربر، توکن JWT خودش رو (همونی که از
 * اپ فلاتر می‌گیره) به بک‌اند PHP روی هاست می‌فرسته و نتیجه رو ازش می‌گیره.
 */
require('dotenv').config();
const express = require('express');
const http = require('http');
const cors = require('cors');
const axios = require('axios');
const { Server } = require('socket.io');

const PHP_API_BASE_URL = (process.env.PHP_API_BASE_URL || '').replace(/\/$/, '');
const PORT = process.env.PORT || 4000;
const ALLOWED_ORIGINS = (process.env.ALLOWED_ORIGINS || '*').split(',');

if (!PHP_API_BASE_URL) {
  console.error('خطا: PHP_API_BASE_URL در .env تنظیم نشده. سرور بدون این مقدار نمی‌تونه کاربرها رو تایید کنه.');
  process.exit(1);
}

const app = express();
app.use(cors({ origin: ALLOWED_ORIGINS }));
app.get('/health', (req, res) => res.json({ ok: true, service: 'nexa-realtime-server' }));

const server = http.createServer(app);
const io = new Server(server, {
  cors: { origin: ALLOWED_ORIGINS, methods: ['GET', 'POST'] },
});

/**
 * state حافظه‌ای (نه دیتابیس!) برای هر room:
 * rooms: Map<sessionId(string), {
 *   participants: Map<socketId, { userId, fullName, role, roleInRoom, isMainTeacher, micOpen, camOpen, handRaised }>,
 *   whiteboardColor: string
 * }>
 */
const rooms = new Map();

function getOrCreateRoom(sessionId) {
  if (!rooms.has(sessionId)) {
    rooms.set(sessionId, { participants: new Map(), whiteboardColor: '#1B3358' });
  }
  return rooms.get(sessionId);
}

function isModerator(participant) {
  return participant && (participant.roleInRoom === 'host' || participant.roleInRoom === 'moderator');
}

function roomParticipantsList(room) {
  return Array.from(room.participants.entries()).map(([socketId, p]) => ({ socketId, ...p }));
}

/** تایید کاربر از طریق بک‌اند PHP (همون توکنی که کاربر با اپ لاگین کرده) */
async function verifyAccess(sessionId, token) {
  const res = await axios.get(`${PHP_API_BASE_URL}/onlineclass/verify-access`, {
    params: { session_id: sessionId },
    headers: { Authorization: `Bearer ${token}` },
    timeout: 8000,
    validateStatus: () => true,
  });
  if (res.status !== 200 || !res.data || res.data.success !== true) {
    const message = (res.data && res.data.message) || 'تایید نشد';
    throw new Error(message);
  }
  return res.data.data;
}

io.on('connection', (socket) => {
  let currentSessionId = null;

  socket.on('join-room', async ({ sessionId, token }) => {
    try {
      if (!sessionId || !token) throw new Error('sessionId و token الزامی است');

      const access = await verifyAccess(String(sessionId), token);
      currentSessionId = String(sessionId);

      const room = getOrCreateRoom(currentSessionId);
      const participant = {
        userId: access.user_id,
        fullName: access.full_name,
        role: access.role,
        roleInRoom: access.role_in_room, // host | moderator | participant
        isMainTeacher: access.is_main_teacher,
        micOpen: false,
        camOpen: false,
        handRaised: false,
      };
      room.participants.set(socket.id, participant);
      socket.join(currentSessionId);

      socket.emit('room-joined', {
        you: { socketId: socket.id, ...participant },
        participants: roomParticipantsList(room).filter((p) => p.socketId !== socket.id),
        whiteboardColor: room.whiteboardColor,
      });

      socket.to(currentSessionId).emit('participant-joined', { socketId: socket.id, ...participant });
    } catch (err) {
      socket.emit('join-error', { message: err.message || 'خطا در ورود به کلاس' });
    }
  });

  // ---- WebRTC signaling relay (peer-to-peer بین معلم/میزبان‌ها و هر دانش‌آموز) ----
  socket.on('webrtc-offer', ({ to, sdp }) => {
    io.to(to).emit('webrtc-offer', { from: socket.id, sdp });
  });
  socket.on('webrtc-answer', ({ to, sdp }) => {
    io.to(to).emit('webrtc-answer', { from: socket.id, sdp });
  });
  socket.on('webrtc-ice-candidate', ({ to, candidate }) => {
    io.to(to).emit('webrtc-ice-candidate', { from: socket.id, candidate });
  });

  // ---- وضعیت میکروفون/دوربین (فقط برای نمایش آیکون؛ خودِ رسانه با replaceTrack کلاینتی مدیریت می‌شه) ----
  socket.on('mic-state-changed', ({ open }) => {
    if (!currentSessionId) return;
    const room = rooms.get(currentSessionId);
    const p = room?.participants.get(socket.id);
    if (!p) return;
    p.micOpen = !!open;
    io.to(currentSessionId).emit('participant-mic-state', { socketId: socket.id, open: p.micOpen });
  });

  socket.on('cam-state-changed', ({ open }) => {
    if (!currentSessionId) return;
    const room = rooms.get(currentSessionId);
    const p = room?.participants.get(socket.id);
    if (!p) return;
    p.camOpen = !!open;
    io.to(currentSessionId).emit('participant-cam-state', { socketId: socket.id, open: p.camOpen });
  });

  // ---- دست‌بالا بردن (فقط دانش‌آموز) و اجازه‌ی صحبت از طرف معلم/میزبان ----
  socket.on('raise-hand', () => {
    if (!currentSessionId) return;
    const room = rooms.get(currentSessionId);
    const p = room?.participants.get(socket.id);
    if (!p) return;
    p.handRaised = true;
    io.to(currentSessionId).emit('hand-raised', { socketId: socket.id, fullName: p.fullName });
  });

  socket.on('lower-hand', () => {
    if (!currentSessionId) return;
    const room = rooms.get(currentSessionId);
    const p = room?.participants.get(socket.id);
    if (!p) return;
    p.handRaised = false;
    io.to(currentSessionId).emit('hand-lowered', { socketId: socket.id });
  });

  /** معلم/میزبان به یک دانش‌آموز خاص اجازه می‌ده میکروفونش رو باز کنه */
  socket.on('allow-unmute', ({ targetSocketId }) => {
    if (!currentSessionId) return;
    const room = rooms.get(currentSessionId);
    const sender = room?.participants.get(socket.id);
    if (!isModerator(sender)) return; // فقط میزبان/ناظر اجازه داره
    const target = room.participants.get(targetSocketId);
    if (target) target.handRaised = false;
    io.to(targetSocketId).emit('mic-permission-granted');
    io.to(currentSessionId).emit('hand-lowered', { socketId: targetSocketId });
  });

  /** معلم/میزبان می‌تونه یه دانش‌آموز رو به‌زور میوت کنه */
  socket.on('force-mute', ({ targetSocketId }) => {
    if (!currentSessionId) return;
    const room = rooms.get(currentSessionId);
    const sender = room?.participants.get(socket.id);
    if (!isModerator(sender)) return;
    io.to(targetSocketId).emit('force-mute');
  });

  // ---- اشتراک‌گذاری صفحه (فقط میزبان/ناظر) ----
  socket.on('screen-share-started', () => {
    if (!currentSessionId) return;
    const room = rooms.get(currentSessionId);
    const p = room?.participants.get(socket.id);
    if (!isModerator(p)) return;
    io.to(currentSessionId).emit('screen-share-started', { socketId: socket.id });
  });
  socket.on('screen-share-stopped', () => {
    if (!currentSessionId) return;
    io.to(currentSessionId).emit('screen-share-stopped', { socketId: socket.id });
  });

  // ---- تخته‌ی مشترک (فقط میزبان/ناظر می‌تونن بکشن و رنگ رو عوض کنن) ----
  socket.on('whiteboard-draw', (stroke) => {
    if (!currentSessionId) return;
    const room = rooms.get(currentSessionId);
    const p = room?.participants.get(socket.id);
    if (!isModerator(p)) return;
    socket.to(currentSessionId).emit('whiteboard-draw', stroke);
  });

  socket.on('whiteboard-color-change', ({ color }) => {
    if (!currentSessionId || !color) return;
    const room = rooms.get(currentSessionId);
    const p = room?.participants.get(socket.id);
    if (!isModerator(p)) return;
    room.whiteboardColor = color;
    socket.to(currentSessionId).emit('whiteboard-color-change', { color });
  });

  socket.on('whiteboard-clear', () => {
    if (!currentSessionId) return;
    const room = rooms.get(currentSessionId);
    const p = room?.participants.get(socket.id);
    if (!isModerator(p)) return;
    socket.to(currentSessionId).emit('whiteboard-clear');
  });

  socket.on('leave-room', () => cleanupParticipant());
  socket.on('disconnect', () => cleanupParticipant());

  function cleanupParticipant() {
    if (!currentSessionId) return;
    const room = rooms.get(currentSessionId);
    if (!room) return;
    room.participants.delete(socket.id);
    socket.to(currentSessionId).emit('participant-left', { socketId: socket.id });
    if (room.participants.size === 0) rooms.delete(currentSessionId);
    currentSessionId = null;
  }
});

server.listen(PORT, () => {
  console.log(`Nexa realtime server listening on port ${PORT}`);
});
