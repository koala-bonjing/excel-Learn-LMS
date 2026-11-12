import { useState } from 'react';
import type { Role } from '@/types';
import { PROFILES } from '@/data/mockData';
import { getCourseData } from '@/data/courseOutlineData';
import { PrototypeSwitcher } from '@/components/layout/PrototypeSwitcher';
import { Shell } from '@/components/layout/Shell';
import { LoginScreen } from '@/components/screens/LoginScreen';
import { StudentDashboard } from '@/components/screens/StudentDashboard';
import { TeacherDashboard } from '@/components/screens/TeacherDashboard';
import { AdminDashboard } from '@/components/screens/AdminDashboard';

export default function App() {
  const [currentRole, setCurrentRole] = useState<Role>('student');

  // Active sub-tabs for each role
  const [studentTab, setStudentTab] = useState<string>('dashboard');
  const [selectedCourseId, setSelectedCourseId] = useState<string | null>('c-social-issues');
  const [activeCourseSectionId, setActiveCourseSectionId] = useState<string | null>(null);

  const [teacherTab, setTeacherTab] = useState<string>('dashboard');
  const [adminTab, setAdminTab] = useState<string>('dashboard');

  const currentCourseData = getCourseData(selectedCourseId);

  const courseSectionNav = {
    courseId: currentCourseData.courseId,
    courseTitle: currentCourseData.fullTitle,
    sections: currentCourseData.sections.map((s) => ({ id: s.id, title: s.title })),
    activeSectionId: activeCourseSectionId,
    onSelectSection: (sectionId: string) => {
      setActiveCourseSectionId(sectionId);
      if (studentTab !== 'courses') {
        setStudentTab('courses');
      }
      setTimeout(() => {
        const el = document.getElementById(sectionId);
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, 50);
    },
    onBackToOverview: () => {
      setStudentTab('dashboard');
    },
    onOpenCourse: (courseId: string) => {
      setSelectedCourseId(courseId);
      setStudentTab('courses');
    },
  };

  const handleRoleSelect = (newRole: Role) => {
    setCurrentRole(newRole);
  };

  const handleLogin = (role: 'student' | 'teacher' | 'admin') => {
    setCurrentRole(role);
  };

  const handleLogout = () => {
    setCurrentRole('login');
  };

  return (
    <div className="min-h-screen w-full bg-bg text-text antialiased">
      {/* Screen 1: Login Portal */}
      {currentRole === 'login' && (
        <>
          <PrototypeSwitcher
            currentRole={currentRole}
            onSelectRole={handleRoleSelect}
          />
          <LoginScreen onLogin={handleLogin} />
        </>
      )}

      {/* Screen 2: Student Dashboard */}
      {currentRole === 'student' && (
        <Shell
          currentRole="student"
          user={PROFILES.student}
          currentTab={studentTab}
          onTabChange={setStudentTab}
          onSelectRole={handleRoleSelect}
          onLogout={handleLogout}
          courseSectionNav={courseSectionNav}
        >
          <StudentDashboard
            activeTab={studentTab}
            onTabChange={setStudentTab}
            selectedCourseId={selectedCourseId}
            onSelectCourse={setSelectedCourseId}
            activeCourseSectionId={activeCourseSectionId}
            onSelectCourseSection={setActiveCourseSectionId}
          />
        </Shell>
      )}

      {/* Screen 3: Teacher Dashboard */}
      {currentRole === 'teacher' && (
        <Shell
          currentRole="teacher"
          user={PROFILES.teacher}
          currentTab={teacherTab}
          onTabChange={setTeacherTab}
          onSelectRole={handleRoleSelect}
          onLogout={handleLogout}
        >
          <TeacherDashboard
            activeTab={teacherTab}
            onTabChange={setTeacherTab}
          />
        </Shell>
      )}

      {/* Screen 4: Admin Dashboard */}
      {currentRole === 'admin' && (
        <Shell
          currentRole="admin"
          user={PROFILES.admin}
          currentTab={adminTab}
          onTabChange={setAdminTab}
          onSelectRole={handleRoleSelect}
          onLogout={handleLogout}
        >
          <AdminDashboard
            activeTab={adminTab}
            onTabChange={setAdminTab}
          />
        </Shell>
      )}
    </div>
  );
}
