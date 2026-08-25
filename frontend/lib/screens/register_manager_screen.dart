import 'package:flutter/material.dart';
import '../main.dart';
import '../services/api_service.dart';
import '../services/settings_service.dart';
import '../widgets/animated_mesh_background.dart';
import '../widgets/glass_card.dart';
import '../widgets/animated_glass_field.dart';
import '../widgets/animated_primary_button.dart';
import 'manager_home.dart';

class RegisterManagerScreen extends StatefulWidget {
  const RegisterManagerScreen({super.key});
  @override
  State<RegisterManagerScreen> createState() => _RegisterManagerScreenState();
}

class _RegisterManagerScreenState extends State<RegisterManagerScreen> {
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  final _schoolNameCtrl = TextEditingController();
  final _nationalCodeCtrl = TextEditingController();
  final _cityCtrl = TextEditingController();
  bool _loading = false;
  String? _error;

  Future<void> _register() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await ApiService.post('auth/register-manager', {
        'full_name': _nameCtrl.text.trim(),
        'phone': _phoneCtrl.text.trim(),
        'password': _passCtrl.text,
        'school_name': _schoolNameCtrl.text.trim(),
        'national_code': _nationalCodeCtrl.text.trim(),
        'city': _cityCtrl.text.trim(),
      });
      if (res['success'] == true) {
        await ApiService.saveSession(res['token'], res['user']);
        appTheme.value = await SchoolTheme.fetch();
        if (!mounted) return;
        Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const ManagerHome()));
      } else {
        setState(() => _error = res['message'] ?? 'خطا در ثبت‌نام');
      }
    } catch (_) {
      setState(() => _error = 'خطا در اتصال به سرور');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final primary = appTheme.value.primary;
    final secondary = appTheme.value.secondary;

    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: AppBar(title: const Text('ثبت‌نام مدیر مدرسه')),
      body: ValueListenableBuilder<bool>(
        valueListenable: SettingsService.animationsEnabled,
        builder: (context, effectsOn, _) {
          return AnimatedMeshBackground(
            enabled: effectsOn,
            primary: primary,
            secondary: secondary,
            showParticles: false,
            child: SafeArea(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(20, 84, 20, 30),
                child: GlassCard(
                  blurEnabled: effectsOn,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Text('اطلاعات مدیر', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.white)),
                      const SizedBox(height: 12),
                      AnimatedGlassField(controller: _nameCtrl, label: 'نام و نام خانوادگی', icon: Icons.person_outline_rounded),
                      const SizedBox(height: 12),
                      AnimatedGlassField(
                        controller: _phoneCtrl,
                        label: 'شماره موبایل',
                        icon: Icons.phone_iphone_rounded,
                        keyboardType: TextInputType.phone,
                      ),
                      const SizedBox(height: 12),
                      AnimatedGlassField(controller: _passCtrl, label: 'رمز عبور', icon: Icons.lock_outline_rounded, obscure: true),
                      const SizedBox(height: 18),
                      const Text('اطلاعات مدرسه', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.white)),
                      const SizedBox(height: 4),
                      const Text('این اطلاعات به‌صورت خودکار اعتبارسنجی می‌شود', style: TextStyle(color: Colors.white54, fontSize: 12)),
                      const SizedBox(height: 12),
                      AnimatedGlassField(controller: _schoolNameCtrl, label: 'نام مدرسه', icon: Icons.school_outlined),
                      const SizedBox(height: 12),
                      AnimatedGlassField(
                        controller: _nationalCodeCtrl,
                        label: 'کد ملی / کد رهگیری مدرسه',
                        icon: Icons.badge_outlined,
                        keyboardType: TextInputType.number,
                      ),
                      const SizedBox(height: 12),
                      AnimatedGlassField(controller: _cityCtrl, label: 'شهر', icon: Icons.location_city_outlined),
                      if (_error != null) ...[
                        const SizedBox(height: 12),
                        Text(_error!, style: const TextStyle(color: Color(0xFFFF6B6B))),
                      ],
                      const SizedBox(height: 18),
                      AnimatedPrimaryButton(
                        label: 'ثبت‌نام و ورود',
                        loading: _loading,
                        color: secondary,
                        onPressed: _register,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
