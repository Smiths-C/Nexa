import 'package:flutter/material.dart';
import '../widgets/dashboard_scaffold.dart';
import 'class_picker_screen.dart';

class ManagerHome extends StatelessWidget {
  const ManagerHome({super.key});

  @override
  Widget build(BuildContext context) {
    return DashboardScaffold(
      title: 'پنل مدیر مدرسه',
      subtitle: 'مدیریت کلاس‌ها، دانش‌آموزان و معلم‌های مدرسه‌ی شما',
      items: [
        DashboardItem(
          title: 'کلاس‌ها',
          icon: Icons.meeting_room_rounded,
          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PlaceholderScreen(title: 'کلاس‌ها'))),
        ),
        DashboardItem(
          title: 'دانش‌آموزان',
          icon: Icons.groups_rounded,
          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PlaceholderScreen(title: 'دانش‌آموزان'))),
        ),
        DashboardItem(
          title: 'معلم‌ها',
          icon: Icons.co_present_rounded,
          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PlaceholderScreen(title: 'معلم‌ها'))),
        ),
        DashboardItem(
          title: 'کلاس آنلاین',
          icon: Icons.videocam_rounded,
          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ClassPickerScreen(role: 'manager'))),
        ),
        DashboardItem(
          title: 'اطلاعیه‌ها',
          icon: Icons.campaign_rounded,
          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PlaceholderScreen(title: 'اطلاعیه‌ها'))),
        ),
      ],
    );
  }
}
