import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../main.dart';
import '../services/api_service.dart';
import '../services/settings_service.dart';
import '../widgets/animated_mesh_background.dart';
import '../widgets/glass_card.dart';
import '../widgets/animated_glass_field.dart';
import '../widgets/animated_primary_button.dart';
import '../widgets/effects_toggle.dart';
import 'admin_home.dart';
import 'manager_home.dart';
import 'teacher_home.dart';
import 'student_home.dart';
import 'register_manager_screen.dart';
import 'school_code_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with TickerProviderStateMixin {
  final _phoneCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _loading = false;
  bool _obscure = true;
  String? _error;

  // انیمیشن ورود مرحله‌ای: لوگو -> عنوان -> کارت شیشه‌ای
  late final AnimationController _entrance;
  late final Animation<double> _logoScale;
  late final Animation<double> _titleFade;
  late final Animation<double> _cardFade;
  late final Animation<Offset> _cardSlide;

  // شناور موندن آرومِ لوگو بعد از ورود (فقط وقتی جلوه‌ها روشنه)
  late final AnimationController _float;

  @override
  void initState() {
    super.initState();
    _entrance = AnimationController(vsync: this, duration: const Duration(milliseconds: 1200));
    _logoScale = CurvedAnimation(parent: _entrance, curve: const Interval(0.0, 0.5, curve: Curves.easeOutBack));
    _titleFade = CurvedAnimation(parent: _entrance, curve: const Interval(0.25, 0.65, curve: Curves.easeOut));
    _cardFade = CurvedAnimation(parent: _entrance, curve: const Interval(0.4, 1.0, curve: Curves.easeOut));
    _cardSlide = Tween(begin: const Offset(0, 0.06), end: Offset.zero)
        .animate(CurvedAnimation(parent: _entrance, curve: const Interval(0.4, 1.0, curve: Curves.easeOutCubic)));
    _entrance.forward();

    _float = AnimationController(vsync: this, duration: const Duration(milliseconds: 3200));
    if (SettingsService.animationsEnabled.value) _float.repeat(reverse: true);
    SettingsService.animationsEnabled.addListener(_syncFloat);
  }

  void _syncFloat() {
    if (SettingsService.animationsEnabled.value) {
      _float.repeat(reverse: true);
    } else {
      _float.stop();
      _float.value = 0;
    }
  }

  @override
  void dispose() {
    SettingsService.animationsEnabled.removeListener(_syncFloat);
    _entrance.dispose();
    _float.dispose();
    _phoneCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await ApiService.post('auth/login', {
        'phone': _phoneCtrl.text.trim(),
        'password': _passCtrl.text,
      });
      if (res['success'] == true) {
        await ApiService.saveSession(res['token'], res['user']);
        appTheme.value = await SchoolTheme.fetch();
        if (!mounted) return;
        _goHome(res['user']['role']);
      } else {
        setState(() => _error = res['message'] ?? 'خطا در ورود');
      }
    } catch (_) {
      setState(() => _error = 'خطا در اتصال به سرور، اتصال اینترنت رو چک کنید');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _goHome(String role) {
    final page = switch (role) {
      'admin' => const AdminHome(),
      'manager' => const ManagerHome(),
      'teacher' => const TeacherHome(),
      _ => const StudentHome(),
    };
    Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => page));
  }

  @override
  Widget build(BuildContext context) {
    final primary = appTheme.value.primary;
    final secondary = appTheme.value.secondary;

    return Scaffold(
      body: ValueListenableBuilder<bool>(
        valueListenable: SettingsService.animationsEnabled,
        builder: (context, effectsOn, _) {
          return AnimatedMeshBackground(
            enabled: effectsOn,
            primary: primary,
            secondary: secondary,
            child: SafeArea(
              child: Stack(
                children: [
                  const Positioned(top: 6, left: 6, child: EffectsToggle()),
                  Center(
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 30),
                      child: Column(
                        children: [
                          ScaleTransition(
                            scale: _logoScale,
                            child: FadeTransition(
                              opacity: _logoScale,
                              child: AnimatedBuilder(
                                animation: _float,
                                builder: (context, child) {
                                  final dy = effectsOn ? (-6 + 12 * _float.value) : 0.0;
                                  return Transform.translate(offset: Offset(0, dy), child: child);
                                },
                                child: Container(
                                  padding: const EdgeInsets.all(6),
                                  decoration: BoxDecoration(
                                    shape: BoxShape.circle,
                                    boxShadow: [
                                      BoxShadow(color: secondary.withOpacity(0.45), blurRadius: 60, spreadRadius: 6),
                                      BoxShadow(color: primary.withOpacity(0.35), blurRadius: 40, spreadRadius: 0),
                                    ],
                                  ),
                                  child: Image.asset('assets/logo/nexa_logo.png', width: 168, fit: BoxFit.contain),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 8),
                          FadeTransition(
                            opacity: _titleFade,
                            child: Column(
                              children: [
                                Text(
                                  'Nexa',
                                  style: GoogleFonts.spaceGrotesk(
                                    color: Colors.white,
                                    fontSize: 34,
                                    fontWeight: FontWeight.w700,
                                    letterSpacing: 1.2,
                                  ),
                                ),
                                const SizedBox(height: 6),
                                const Text('همه‌چیز مدرسه، یک‌جا', style: TextStyle(color: Colors.white60, fontSize: 13)),
                              ],
                            ),
                          ),
                          const SizedBox(height: 32),
                          FadeTransition(
                            opacity: _cardFade,
                            child: SlideTransition(
                              position: _cardSlide,
                              child: GlassCard(
                                blurEnabled: effectsOn,
                                child: Column(
                                  children: [
                                    AnimatedGlassField(
                                      controller: _phoneCtrl,
                                      label: 'شماره موبایل',
                                      icon: Icons.phone_iphone_rounded,
                                      keyboardType: TextInputType.phone,
                                    ),
                                    const SizedBox(height: 14),
                                    AnimatedGlassField(
                                      controller: _passCtrl,
                                      label: 'رمز عبور',
                                      icon: Icons.lock_outline_rounded,
                                      obscure: _obscure,
                                      suffix: IconButton(
                                        icon: Icon(_obscure ? Icons.visibility_off_rounded : Icons.visibility_rounded, color: Colors.white54),
                                        onPressed: () => setState(() => _obscure = !_obscure),
                                      ),
                                    ),
                                    if (_error != null) ...[
                                      const SizedBox(height: 10),
                                      Align(
                                        alignment: Alignment.centerRight,
                                        child: Text(_error!, style: const TextStyle(color: Color(0xFFFF6B6B), fontSize: 13)),
                                      ),
                                    ],
                                    const SizedBox(height: 20),
                                    AnimatedPrimaryButton(
                                      label: 'ورود',
                                      loading: _loading,
                                      color: secondary,
                                      onPressed: _login,
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 18),
                          FadeTransition(
                            opacity: _cardFade,
                            child: TextButton(
                              onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const RegisterManagerScreen())),
                              child: Text('ثبت‌نام مدیر مدرسه جدید', style: TextStyle(color: secondary, fontWeight: FontWeight.w600)),
                            ),
                          ),
                          if (ApiService.useHub)
                            FadeTransition(
                              opacity: _cardFade,
                              child: TextButton(
                                onPressed: () async {
                                  await ApiService.forgetSchool();
                                  if (!mounted) return;
                                  Navigator.pushReplacement(
                                    context,
                                    MaterialPageRoute(builder: (_) => const SchoolCodeScreen()),
                                  );
                                },
                                child: const Text('مدرسه‌ی دیگری دارید؟ تغییر کد مدرسه',
                                    style: TextStyle(color: Colors.white38, fontSize: 12)),
                              ),
                            ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
