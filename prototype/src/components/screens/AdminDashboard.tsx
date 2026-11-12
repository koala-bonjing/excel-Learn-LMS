import { useState } from 'react';
import type { FacultyApproval, StudentDirectoryItem } from '@/types';
import {
  INITIAL_FACULTY_APPROVALS,
  SHS_STRANDS,
  STUDENT_DIRECTORY,
} from '@/data/mockData';
import { AboutView } from '@/components/views/AboutView';
import {
  RiCheckLine,
  RiCloseLine,
  RiSearchLine,
  RiFileExcel2Line,
  RiAddLine,
  RiUpload2Line,
  RiFileChartLine,
  RiDownload2Line,
  RiPrinterLine,
  RiGroupLine,
  RiUserStarLine,
  RiGraduationCapLine,
  RiBookOpenLine,
  RiBuilding4Line,
  RiDatabase2Line,
  RiNotification3Line,
  RiShieldCheckLine,
  RiFileList3Line,
  RiBarChartLine,
  RiEditLine,
  RiDeleteBinLine,
  RiHardDriveLine,
  RiArrowRightLine,
} from '@remixicon/react';

interface AdminDashboardProps {
  activeTab: string;
  onTabChange: (tab: string) => void;
}

export function AdminDashboard({ activeTab, onTabChange }: AdminDashboardProps) {
  const [approvals, setApprovals] = useState<FacultyApproval[]>(INITIAL_FACULTY_APPROVALS);
  const [students, setStudents] = useState<StudentDirectoryItem[]>(STUDENT_DIRECTORY);
  const [studentSearch, setStudentSearch] = useState('');
  const [selectedStrandFilter, setSelectedStrandFilter] = useState('ALL');

  // Modals state matching admin/*.php
  const [isAddStudentOpen, setIsAddStudentOpen] = useState(false);
  const [isCsvModalOpen, setIsCsvModalOpen] = useState(false);
  const [isAddTeacherOpen, setIsAddTeacherOpen] = useState(false);
  const [isAddSubjectOpen, setIsAddSubjectOpen] = useState(false);
  const [isPostAnnounceOpen, setIsPostAnnounceOpen] = useState(false);
  const [adminAnnSearch, setAdminAnnSearch] = useState('');

  // Add Student form state (admin_students.php lines 1130-1235)
  const [newFirst, setNewFirst] = useState('');
  const [newMiddle, setNewMiddle] = useState('');
  const [newLast, setNewLast] = useState('');
  const [newSuffix, setNewSuffix] = useState('');
  const [newLrn, setNewLrn] = useState('');
  const [newStudId, setNewStudId] = useState('');
  const [newBirthdate, setNewBirthdate] = useState('');
  const [newGender, setNewGender] = useState('Male');
  const [newContact, setNewContact] = useState('');
  const [newAddress, setNewAddress] = useState('');
  const [newGrade, setNewGrade] = useState('Grade 11');
  const [newSection, setNewSection] = useState('STEM 11-A');
  const [newPassword, setNewPassword] = useState('Admin@123');

  // Add Subject form state (admin_subjects.php)
  const [subCode, setSubCode] = useState('');
  const [subTitle, setSubTitle] = useState('');
  const [subGrade, setSubGrade] = useState('Grade 11');
  const [subStrand, setSubStrand] = useState('STEM');

  // Settings State
  const [currentPass, setCurrentPass] = useState('');
  const [newPass, setNewPass] = useState('');
  const [confirmPass, setConfirmPass] = useState('');
  const [passUpdated, setPassUpdated] = useState(false);

  const handleApproveFaculty = (id: string) => {
    setApprovals((prev) =>
      prev.map((a) => (a.id === id ? { ...a, status: 'approved' } : a))
    );
  };

  const handleRejectFaculty = (id: string) => {
    setApprovals((prev) =>
      prev.map((a) => (a.id === id ? { ...a, status: 'rejected' } : a))
    );
  };

  const handleAddStudentSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newFirst || !newLast || !newLrn) return;
    const newStudent: StudentDirectoryItem = {
      id: `sd-${Date.now()}`,
      lrn: newLrn,
      studentId: newStudId || `STUD-2026-${Math.floor(10000 + Math.random() * 90000)}`,
      name: `${newFirst} ${newMiddle ? newMiddle + ' ' : ''}${newLast}${newSuffix ? ' ' + newSuffix : ''}`,
      strand: newSection.includes('STEM') ? 'STEM' : newSection.includes('ABM') ? 'ABM' : 'HUMSS',
      gradeLevel: newGrade,
      section: newSection,
      status: 'Active',
      gwa: 90.0,
    };
    setStudents([newStudent, ...students]);
    setIsAddStudentOpen(false);
    // Reset
    setNewFirst('');
    setNewLast('');
    setNewLrn('');
  };

  const filteredStudents = students.filter((s) => {
    const matchSearch =
      s.name.toLowerCase().includes(studentSearch.toLowerCase()) ||
      s.studentId.toLowerCase().includes(studentSearch.toLowerCase()) ||
      s.lrn.includes(studentSearch);
    const matchStrand =
      selectedStrandFilter === 'ALL' || s.strand === selectedStrandFilter;
    return matchSearch && matchStrand;
  });

  return (
    <div className="space-y-6">
      {/* ============================================================ */}
      {/* 1. EXECUTIVE DASHBOARD (admin/index.php lines 430-718)       */}
      {/* ============================================================ */}
      {activeTab === 'dashboard' && (
        <div className="space-y-6">
          {/* Section Header (admin/index.php lines 430-435) */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
            <div>
              <div className="flex items-center gap-2">
                <h1 className="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-text">
                  Good morning, Admin!
                </h1>
                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25">
                  ● Systems Operational
                </span>
              </div>
              <p className="text-xs sm:text-sm text-text2 mt-1">
                Institutional Control Panel · Senior High School Management & Analytics
              </p>
            </div>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => setIsPostAnnounceOpen(true)}
                className="btn btn-primary btn-sm text-xs cursor-pointer shadow-sm hover:shadow-md inline-flex items-center gap-1.5"
              >
                <RiAddLine size={15} />
                <span>Post System Notice</span>
              </button>
            </div>
          </div>

          {/* Stats Grid - 6 Stat Cards (admin/index.php lines 437-474) */}
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div className="stat-card blue group">
              <div className="stat-icon">
                <RiGroupLine size={22} />
              </div>
              <div className="stat-value">510</div>
              <div className="stat-label">Total Users</div>
              <div className="stat-change up">
                <span>↑ Active accounts</span>
              </div>
            </div>
            <div className="stat-card green group">
              <div className="stat-icon">
                <RiUserStarLine size={22} />
              </div>
              <div className="stat-value">24</div>
              <div className="stat-label">Active Faculty</div>
              <div className="stat-change up">
                <span>Verified staff</span>
              </div>
            </div>
            <div className="stat-card cyan group">
              <div className="stat-icon">
                <RiGraduationCapLine size={22} />
              </div>
              <div className="stat-value">486</div>
              <div className="stat-label">Students</div>
              <div className="stat-change up">
                <span>Enrolled learners</span>
              </div>
            </div>
            <div className="stat-card purple group">
              <div className="stat-icon">
                <RiBookOpenLine size={22} />
              </div>
              <div className="stat-value">5</div>
              <div className="stat-label">Total Strands</div>
              <div className="stat-change up">
                <span>SHS tracks</span>
              </div>
            </div>
            <div className="stat-card teal group">
              <div className="stat-icon">
                <RiBuilding4Line size={22} />
              </div>
              <div className="stat-value">12</div>
              <div className="stat-label">Sections</div>
              <div className="stat-change neutral">
                <span>Class blocks</span>
              </div>
            </div>
            <div className="stat-card amber group">
              <div className="stat-icon">
                <RiDatabase2Line size={22} />
              </div>
              <div className="stat-value">1,420</div>
              <div className="stat-label">Total Records</div>
              <div className="stat-change up">
                <span>System data</span>
              </div>
            </div>
          </div>

          {/* System Announcements Card (admin/index.php lines 476-566) */}
          <div className="card">
            <div className="card-header">
              <div className="card-title">
                <div className="p-1.5 rounded-lg bg-accent/10 text-accent">
                  <RiNotification3Line size={18} />
                </div>
                <span>System Announcements</span>
              </div>
              <button
                type="button"
                onClick={() => setIsPostAnnounceOpen(true)}
                className="btn btn-primary btn-sm text-xs cursor-pointer inline-flex items-center gap-1"
              >
                <RiAddLine size={14} />
                <span>Post Notice</span>
              </button>
            </div>

            {/* Search Input */}
            <div className="relative mb-4">
              <RiSearchLine size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-text3 pointer-events-none" />
              <input
                type="text"
                value={adminAnnSearch}
                onChange={(e) => setAdminAnnSearch(e.target.value)}
                placeholder="Search institutional announcements by title or target audience..."
                className="w-full pl-9 pr-8 py-2 rounded-xl border border-border bg-bg3 text-xs text-text placeholder:text-text3 outline-none focus:border-accent focus:ring-2 focus:ring-accent/15 transition-all"
              />
              {adminAnnSearch && (
                <button
                  type="button"
                  onClick={() => setAdminAnnSearch('')}
                  className="absolute right-2.5 top-1/2 -translate-y-1/2 text-text3 hover:text-text p-0.5 cursor-pointer"
                >
                  <RiCloseLine size={14} />
                </button>
              )}
            </div>

            <div className="space-y-3">
              {[
                {
                  id: 1,
                  type: 'important',
                  posterRole: 'admin',
                  posterName: 'ExceLearn Admin',
                  category: 'both',
                  catLabel: 'Everyone',
                  catColor: 'var(--green)',
                  date: 'May 14, 09:00 AM',
                  title: 'Academic Year 2026–2027 Portal Upgrades & Matatag Sync',
                  message:
                    'All teachers and students are advised that the DepEd K-12 MATATAG competencies sync is now operational across all learning sections.',
                },
                {
                  id: 2,
                  type: 'system',
                  posterRole: 'admin',
                  posterName: 'ExceLearn Admin',
                  category: 'for_teacher',
                  catLabel: 'Teachers Only',
                  catColor: 'var(--purple)',
                  date: 'May 12, 03:30 PM',
                  title: 'Faculty Quarterly Grading Deadline & LIS Encoding',
                  message:
                    'Quarterly exams must be scheduled and periodic grade components finalized in the teacher gradebook by Friday next week.',
                },
                {
                  id: 3,
                  type: 'info',
                  posterRole: 'admin',
                  posterName: 'ExceLearn Admin',
                  category: 'for_student',
                  catLabel: 'Students Only',
                  catColor: 'var(--cyan)',
                  date: 'May 10, 11:15 AM',
                  title: 'Virtual Class Attendance Standards & Room Links',
                  message:
                    'Virtual classes are monitored for official DepEd SF2 attendance. Ensure audio/video devices are pre-tested before sessions.',
                },
              ]
                .filter(
                  (a) =>
                    !adminAnnSearch ||
                    a.title.toLowerCase().includes(adminAnnSearch.toLowerCase()) ||
                    a.message.toLowerCase().includes(adminAnnSearch.toLowerCase())
                )
                .map((a) => (
                  <div key={a.id} className={`announce-item ${a.type} space-y-1.5`}>
                    <div className="announce-header flex flex-wrap items-center gap-2">
                      <span className={`announce-tag ${a.type}`}>{a.type}</span>
                      <span className="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-red-500/10 text-red border border-red-500/20">
                        <RiShieldCheckLine size={12} />
                        Admin: {a.posterName}
                      </span>
                      <span
                        className="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full"
                        style={{
                          background: `${a.catColor}18`,
                          color: a.catColor,
                        }}
                      >
                        Target: {a.catLabel}
                      </span>
                      <span className="announce-time text-[11px] text-text3 ml-auto font-medium">{a.date}</span>
                    </div>
                    <div className="text-sm font-bold text-text pt-0.5">{a.title}</div>
                    <div className="text-xs text-text2 leading-relaxed">{a.message}</div>
                    <div className="flex gap-2 pt-2 border-t border-border/50">
                      <button
                        type="button"
                        className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-medium border border-border hover:bg-bg4 text-text2 hover:text-text cursor-pointer transition-colors"
                      >
                        <RiEditLine size={13} />
                        <span>Edit</span>
                      </button>
                      <button
                        type="button"
                        className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-medium bg-red-500/10 text-red border border-red-500/20 hover:bg-red-500/20 cursor-pointer transition-colors"
                      >
                        <RiDeleteBinLine size={13} />
                        <span>Delete</span>
                      </button>
                    </div>
                  </div>
                ))}
            </div>
          </div>

          {/* Dash Grid 1: Quick Actions & Recent Registrations (admin/index.php lines 568-635) */}
          <div className="dash-grid dash-grid-2-1">
            {/* Quick Actions Card */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-amber/10 text-amber">
                    <RiFileList3Line size={18} />
                  </div>
                  <span>Quick Actions</span>
                </div>
              </div>
              <div className="quick-actions">
                <div
                  className="quick-action group"
                  onClick={() => onTabChange('teachers')}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiUserStarLine size={22} />
                  </span>
                  <span className="qa-label">Add Faculty</span>
                </div>
                <div
                  className="quick-action group"
                  onClick={() => setIsAddStudentOpen(true)}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiGraduationCapLine size={22} />
                  </span>
                  <span className="qa-label">Add Student</span>
                </div>
                <div
                  className="quick-action group"
                  onClick={() => onTabChange('subjects')}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiBookOpenLine size={22} />
                  </span>
                  <span className="qa-label">New Subject</span>
                </div>
                <div
                  className="quick-action group"
                  onClick={() => onTabChange('sections')}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiBuilding4Line size={22} />
                  </span>
                  <span className="qa-label">New Section</span>
                </div>
                <div
                  className="quick-action group"
                  onClick={() => onTabChange('reports')}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiFileList3Line size={22} />
                  </span>
                  <span className="qa-label">Reports</span>
                </div>
                <div
                  className="quick-action group"
                  onClick={() => onTabChange('analytics')}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiBarChartLine size={22} />
                  </span>
                  <span className="qa-label">Analytics</span>
                </div>
              </div>
            </div>

            {/* Recent Registrations Card */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-accent/10 text-accent">
                    <RiGroupLine size={18} />
                  </div>
                  <span>Recent Registrations</span>
                </div>
                <button
                  type="button"
                  onClick={() => onTabChange('students')}
                  className="btn btn-ghost btn-sm text-xs cursor-pointer inline-flex items-center gap-1"
                >
                  <span>Directory</span>
                  <RiArrowRightLine size={13} />
                </button>
              </div>
              <div className="space-y-2">
                {[
                  { name: 'Ms. Juna Velarde', role: 'Teacher', date: 'May 14', verified: true, bg: 'linear-gradient(135deg, var(--cyan), var(--accent))', initials: 'JV' },
                  { name: 'Bryan Donceras', role: 'Student', date: 'May 12', verified: true, bg: 'linear-gradient(135deg, var(--green), #1aaa6e)', initials: 'BD' },
                  { name: 'Janloyd Lotino', role: 'Student', date: 'May 11', verified: true, bg: 'linear-gradient(135deg, var(--purple), #8b3fbf)', initials: 'JL' },
                  { name: 'Katrina Diaz', role: 'Student', date: 'May 10', verified: true, bg: 'linear-gradient(135deg, #f5a623, #e08c0c)', initials: 'KD' },
                  { name: 'Mr. Ryan Alcantara', role: 'Teacher', date: 'May 08', verified: false, bg: 'linear-gradient(135deg, var(--red), #e04040)', initials: 'RA' },
                ].map((u, idx) => (
                  <div key={idx} className="user-row hover:border-border2 transition-colors">
                    <div className="user-row-avatar shadow-xs" style={{ background: u.bg }}>
                      {u.initials}
                    </div>
                    <div className="user-row-info">
                      <div className="user-row-name text-xs font-semibold text-text">{u.name}</div>
                      <div className="user-row-detail text-[11px] text-text3">{u.role} · Reg {u.date}</div>
                    </div>
                    <div className="user-row-role">
                      <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${
                        u.role === 'Teacher' ? 'bg-purple-500/10 text-purple' : 'bg-accent/10 text-accent'
                      }`}>
                        {u.role}
                      </span>
                    </div>
                    <div className="user-row-status">
                      <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${
                        u.verified
                          ? 'bg-green-500/10 text-green border border-green-500/20'
                          : 'bg-amber-500/10 text-amber border border-amber-500/20'
                      }`}>
                        {u.verified ? 'Active' : 'Pending'}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Dash Grid 2: Available Strands & Storage Usage (admin/index.php lines 636-718) */}
          <div className="dash-grid dash-grid-1-1">
            {/* Available Strands */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-teal/10 text-teal">
                    <RiBuilding4Line size={18} />
                  </div>
                  <span>Available Strands</span>
                </div>
                <span className="badge badge-cyan">5 tracks active</span>
              </div>
              <div className="space-y-3.5">
                {[
                  { name: 'STEM', track: 'Science, Technology, Engineering & Math', students: 168, color: 'var(--accent)' },
                  { name: 'ABM', track: 'Accountancy, Business & Management', students: 112, color: 'var(--green)' },
                  { name: 'HUMSS', track: 'Humanities & Social Sciences', students: 96, color: 'var(--amber)' },
                  { name: 'TVL-ICT', track: 'Technical-Vocational Livelihood - ICT', students: 74, color: 'var(--purple)' },
                  { name: 'GAS', track: 'General Academic Strand', students: 36, color: 'var(--cyan)' },
                ].map((s, idx) => (
                  <div key={idx} className="p-2 -mx-2 rounded-xl hover:bg-bg3/60 transition-colors">
                    <div className="flex items-center justify-between text-xs mb-1.5">
                      <div className="font-semibold text-text">
                        <span>{s.name}</span>
                        <span className="text-text3 font-normal ml-2 text-[11px] hidden sm:inline">({s.track})</span>
                      </div>
                      <span className="text-text2 font-bold tabular-nums text-[11px]">{s.students} learners</span>
                    </div>
                    <div className="progress-bar">
                      <div
                        className="progress-fill"
                        style={{
                          width: `${(s.students / 200) * 100}%`,
                          background: s.color,
                        }}
                      />
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Storage Usage */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-purple/10 text-purple">
                    <RiHardDriveLine size={18} />
                  </div>
                  <span>Storage Usage</span>
                </div>
                <span className="badge badge-purple">20.0 GB / 50.0 GB (40%)</span>
              </div>
              <div>
                <div className="storage-wrap mb-4">
                  <div className="storage-header flex justify-between text-xs mb-2 font-medium">
                    <span className="text-text2">Cloud Filesystem Allocation</span>
                    <span className="text-accent font-semibold">20.0 GB Used · 30.0 GB Free</span>
                  </div>
                  <div className="h-3 w-full bg-bg3 border border-border rounded-full overflow-hidden flex shadow-xs">
                    <div style={{ width: '8.4%', background: 'var(--accent)' }} title="Course Materials: 4.2 GB" />
                    <div style={{ width: '17%', background: 'var(--green)' }} title="Videos: 8.5 GB" />
                    <div style={{ width: '4.2%', background: 'var(--purple)' }} title="PDF: 2.1 GB" />
                    <div style={{ width: '2.8%', background: 'var(--amber)' }} title="PPTX: 1.4 GB" />
                    <div style={{ width: '7.6%', background: 'var(--red)' }} title="Backups: 3.8 GB" />
                  </div>
                </div>

                <div className="space-y-2.5">
                  {[
                    { label: 'Course Materials', gb: '4.2 GB', pct: 21, color: 'var(--accent)' },
                    { label: 'Recorded Virtual Classes', gb: '8.5 GB', pct: 42.5, color: 'var(--green)' },
                    { label: 'Student PDF Submissions', gb: '2.1 GB', pct: 10.5, color: 'var(--purple)' },
                    { label: 'Presentation Slides (PPTX)', gb: '1.4 GB', pct: 7.0, color: 'var(--amber)' },
                    { label: 'Database Backups', gb: '3.8 GB', pct: 19.0, color: 'var(--red)' },
                  ].map((item, idx) => (
                    <div key={idx} className="flex items-center gap-3 text-xs">
                      <div className="w-36 text-xs text-text2 font-medium truncate">{item.label}</div>
                      <div className="flex-1">
                        <div className="progress-bar">
                          <div
                            className="progress-fill"
                            style={{
                              width: `${item.pct}%`,
                              background: item.color,
                            }}
                          />
                        </div>
                      </div>
                      <div className="w-16 text-right text-xs font-bold tabular-nums" style={{ color: item.color }}>
                        {item.gb}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* POST ANNOUNCEMENT MODAL FOR ADMIN (admin/index.php lines 720-770) */}
      {isPostAnnounceOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-md bg-bg2 rounded-2xl border border-border shadow-2xl overflow-hidden p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <h4 className="text-base font-bold text-text">📢 Post System Announcement</h4>
              <button
                type="button"
                onClick={() => setIsPostAnnounceOpen(false)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            <form
              onSubmit={(e) => {
                e.preventDefault();
                setIsPostAnnounceOpen(false);
              }}
              className="space-y-3 text-xs"
            >
              <div>
                <label className="block font-semibold text-text2 mb-1">Title *</label>
                <input
                  type="text"
                  placeholder="Announcement title..."
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                  required
                />
              </div>
              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block font-semibold text-text2 mb-1">Type</label>
                  <select className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent">
                    <option value="important">Important</option>
                    <option value="system">System</option>
                    <option value="info">Info</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-text2 mb-1">Audience</label>
                  <select className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent">
                    <option value="both">👥 Everyone</option>
                    <option value="for_teacher">👨‍🏫 Teachers Only</option>
                    <option value="for_student">👨‍🎓 Students Only</option>
                  </select>
                </div>
              </div>
              <div>
                <label className="block font-semibold text-text2 mb-1">Message *</label>
                <textarea
                  rows={4}
                  placeholder="Write your announcement here..."
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent resize-vertical"
                  required
                />
              </div>
              <div className="flex justify-end gap-2 pt-2 border-t border-border">
                <button
                  type="button"
                  onClick={() => setIsPostAnnounceOpen(false)}
                  className="px-3 py-1.5 rounded-lg border border-border text-text2 hover:bg-bg3 cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 rounded-lg bg-accent text-white font-semibold hover:bg-accent2 cursor-pointer shadow-xs"
                >
                  Post Announcement
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 2. FACULTY MANAGEMENT (admin/admin_teachers.php)             */}
      {/* ============================================================ */}
      {activeTab === 'teachers' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h4 className="text-lg font-bold text-text font-serif">Faculty Approvals & Verification Directory</h4>
              <p className="text-xs text-text2">Review teacher credentials, PRC registration, and assign teaching loads.</p>
            </div>
            <button
              type="button"
              onClick={() => setIsAddTeacherOpen(true)}
              className="px-3 py-1.5 rounded-lg bg-school-orange text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs flex items-center gap-1.5"
            >
              <RiAddLine className="size-4" />
              <span>+ Add Faculty Member</span>
            </button>
          </div>

          <div className="grid grid-cols-1 gap-3">
            {approvals.map((fac) => {
              const isApproved = fac.status === 'approved';
              const isRejected = fac.status === 'rejected';

              return (
                <div
                  key={fac.id}
                  className={`p-4 rounded-xl border transition-all ${
                    isApproved
                      ? 'bg-green-bg/20 border-green/30'
                      : isRejected
                      ? 'bg-red-bg/20 border-red/30'
                      : 'bg-bg2 border-border hover:border-accent/40 shadow-xs'
                  }`}
                >
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <span className="text-sm font-bold text-text">{fac.name}</span>
                        <span className="text-xs text-text3">{fac.email}</span>
                        <span
                          className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                            isApproved
                              ? 'bg-green-bg text-green'
                              : isRejected
                              ? 'bg-red-bg text-red'
                              : 'bg-amber-bg text-amber'
                          }`}
                        >
                          {isApproved ? 'Approved' : isRejected ? 'Rejected' : 'Pending Review'}
                        </span>
                      </div>

                      <div className="text-xs text-text2 font-medium">
                        {fac.department} · {fac.strand}
                      </div>

                      <div className="text-[11px] text-text3 flex items-center gap-3">
                        <span className="text-accent font-mono font-medium">{fac.prcLicense}</span>
                        <span>Submitted {fac.submittedAt}</span>
                      </div>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                      {!isApproved && !isRejected ? (
                        <>
                          <button
                            type="button"
                            onClick={() => handleRejectFaculty(fac.id)}
                            className="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-red/40 text-red text-xs font-medium hover:bg-red/10 cursor-pointer"
                          >
                            <RiCloseLine className="size-4" />
                            <span>Reject</span>
                          </button>
                          <button
                            type="button"
                            onClick={() => handleApproveFaculty(fac.id)}
                            className="flex items-center gap-1 px-3 py-1.5 rounded-lg bg-green text-[#0e0f14] text-xs font-semibold hover:brightness-105 active:scale-95 transition-all cursor-pointer shadow-xs"
                          >
                            <RiCheckLine className="size-4" />
                            <span>Approve Faculty</span>
                          </button>
                        </>
                      ) : (
                        <span className="text-xs font-semibold text-text3">
                          {isApproved ? 'Verified & Onboarded' : 'Application Rejected'}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 3. STUDENT MASTERLIST (admin/admin_students.php)             */}
      {/* ============================================================ */}
      {activeTab === 'students' && (
        <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h4 className="text-lg font-bold text-text font-serif">Official Student Masterlist (DepEd LRN)</h4>
              <p className="text-xs text-text2">Certified learner records with DepEd Learner Reference Numbers.</p>
            </div>

            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => setIsCsvModalOpen(true)}
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
              >
                <RiFileExcel2Line className="size-4" />
                <span>Import CSV / Excel</span>
              </button>
              <button
                type="button"
                onClick={() => setIsAddStudentOpen(true)}
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer shadow-xs"
              >
                <RiAddLine className="size-4" />
                <span>+ Add Student</span>
              </button>
            </div>
          </div>

          {/* Search & Filter Toolbar */}
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <RiSearchLine className="absolute left-3 top-2.5 size-4 text-text3" aria-hidden="true" />
              <input
                type="text"
                placeholder="Search student name, Student ID, or LRN..."
                value={studentSearch}
                onChange={(e) => setStudentSearch(e.target.value)}
                className="w-full pl-9 pr-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:outline-none focus:border-purple"
              />
            </div>

            <select
              value={selectedStrandFilter}
              onChange={(e) => setSelectedStrandFilter(e.target.value)}
              className="px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:outline-none focus:border-purple"
            >
              <option value="ALL">All Strands</option>
              <option value="STEM">STEM</option>
              <option value="ABM">ABM</option>
              <option value="HUMSS">HUMSS</option>
              <option value="TVL-ICT">TVL-ICT</option>
              <option value="GAS">GAS</option>
            </select>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-bg3 text-text3 uppercase tracking-wider border-b border-border">
                <tr>
                  <th className="py-2.5 px-3 font-semibold">Student Name</th>
                  <th className="py-2.5 px-3 font-semibold">Student ID</th>
                  <th className="py-2.5 px-3 font-semibold">DepEd LRN</th>
                  <th className="py-2.5 px-3 font-semibold">Section & Strand</th>
                  <th className="py-2.5 px-3 font-semibold text-center">GWA</th>
                  <th className="py-2.5 px-3 font-semibold text-right">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {filteredStudents.map((st) => (
                  <tr key={st.id} className="hover:bg-bg3/50 transition-colors">
                    <td className="py-3 px-3 font-semibold text-text">{st.name}</td>
                    <td className="py-3 px-3 font-mono text-text3">{st.studentId}</td>
                    <td className="py-3 px-3 font-mono text-accent">{st.lrn}</td>
                    <td className="py-3 px-3">
                      <span className="px-2 py-0.5 rounded text-[10px] font-semibold bg-bg4 text-text2">
                        {st.section}
                      </span>
                    </td>
                    <td className="py-3 px-3 text-center font-bold text-text tabular-nums">{st.gwa}</td>
                    <td className="py-3 px-3 text-right">
                      <span
                        className={`px-2 py-0.5 rounded-full text-[10px] font-semibold ${
                          st.status === 'Active'
                            ? 'bg-green-bg text-green'
                            : 'bg-amber-bg text-amber'
                        }`}
                      >
                        {st.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 4. SUBJECTS CATALOG (admin/admin_subjects.php)               */}
      {/* ============================================================ */}
      {activeTab === 'subjects' && (
        <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h4 className="text-lg font-bold text-text font-serif">Curriculum Subjects Catalog</h4>
              <p className="text-xs text-text2">Official Senior High School DepEd Subject Masterlist.</p>
            </div>
            <button
              type="button"
              onClick={() => setIsAddSubjectOpen(true)}
              className="px-3 py-1.5 rounded-lg bg-school-orange text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs flex items-center gap-1.5"
            >
              <RiAddLine className="size-4" />
              <span>+ Add Subject</span>
            </button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div className="p-4 rounded-xl bg-bg3 border border-border space-y-2">
              <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-accent/15 text-accent">GEN-MATH-11</span>
              <h5 className="text-sm font-bold text-text">General Mathematics</h5>
              <p className="text-xs text-text3">Grade 11 · Core Subject across all strands</p>
            </div>
            <div className="p-4 rounded-xl bg-bg3 border border-border space-y-2">
              <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-cyan-bg text-cyan">ORAL-COMM-11</span>
              <h5 className="text-sm font-bold text-text">Oral Communication in Context</h5>
              <p className="text-xs text-text3">Grade 11 · Core Subject across all strands</p>
            </div>
            <div className="p-4 rounded-xl bg-bg3 border border-border space-y-2">
              <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-green-bg text-green">EARTH-SCI-11</span>
              <h5 className="text-sm font-bold text-text">Earth and Life Science</h5>
              <p className="text-xs text-text3">Grade 11 · STEM Specialized Core</p>
            </div>
            <div className="p-4 rounded-xl bg-bg3 border border-border space-y-2">
              <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-purple-bg text-purple">EMP-TECH-11</span>
              <h5 className="text-sm font-bold text-text">Empowerment Technologies (ICT)</h5>
              <p className="text-xs text-text3">Grade 11 · Applied Subject</p>
            </div>
            <div className="p-4 rounded-xl bg-bg3 border border-border space-y-2">
              <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-bg text-amber">KOM-FIL-11</span>
              <h5 className="text-sm font-bold text-text">Komunikasyon at Pananaliksik</h5>
              <p className="text-xs text-text3">Grade 11 · Asignaturang Pangkalahatan</p>
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 5. STRANDS & SECTIONS (admin_strands.php / admin_sections.php)*/}
      {/* ============================================================ */}
      {activeTab === 'strands' && (
        <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
          <div>
            <h4 className="text-lg font-bold text-text font-serif">SHS Strands & Section Capacities</h4>
            <p className="text-xs text-text2">DepEd Track Distribution for Academic Year 2026–2027.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {SHS_STRANDS.map((strand) => {
              const fillPercent = Math.round((strand.enrolled / strand.capacity) * 100);
              return (
                <div key={strand.code} className="p-4 rounded-xl bg-bg3 border border-border space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold px-2 py-0.5 rounded bg-accent/15 text-accent uppercase tracking-wider">
                      {strand.code}
                    </span>
                    <span className="text-xs text-text3">{strand.sectionsCount} Sections</span>
                  </div>

                  <div>
                    <h5 className="text-sm font-bold text-text">{strand.name}</h5>
                    <div className="flex items-center justify-between text-xs text-text3 mt-2 mb-1">
                      <span>Enrollment</span>
                      <span className="tabular-nums font-semibold text-text">
                        {strand.enrolled} / {strand.capacity} ({fillPercent}%)
                      </span>
                    </div>
                    <div className="h-2 w-full bg-bg4 rounded-full overflow-hidden">
                      <div className="h-full bg-accent rounded-full transition-all" style={{ width: `${fillPercent}%` }} />
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 5b. GRADE & SECTIONS (admin_sections.php)                     */}
      {/* ============================================================ */}
      {activeTab === 'sections' && (
        <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 className="text-base font-bold text-text">Grade Levels & Sections</h3>
              <p className="text-xs text-text2">Class section assignments, advisers, and room allocations.</p>
            </div>
            <button
              type="button"
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 shadow-xs cursor-pointer"
            >
              <RiAddLine className="size-4" />
              <span>Create Section</span>
            </button>
          </div>

          <div className="overflow-x-auto rounded-xl border border-border">
            <table className="w-full text-left text-xs">
              <thead className="bg-bg3 border-b border-border text-text3 uppercase font-semibold text-[10px] tracking-wider">
                <tr>
                  <th className="px-4 py-3">Section Name</th>
                  <th className="px-4 py-3">Grade Level</th>
                  <th className="px-4 py-3">Strand</th>
                  <th className="px-4 py-3">Assigned Adviser</th>
                  <th className="px-4 py-3">Classroom</th>
                  <th className="px-4 py-3 text-right">Students</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {[
                  { section: 'STEM 11-A', grade: 'Grade 11', strand: 'STEM', adviser: 'Ms. Juna Velarde', room: 'Bldg 2 · Rm 204', count: 42, cap: 45 },
                  { section: 'STEM 11-B', grade: 'Grade 11', strand: 'STEM', adviser: 'Mr. Ryan Alcantara', room: 'Bldg 2 · Rm 205', count: 40, cap: 45 },
                  { section: 'ABM 11-A', grade: 'Grade 11', strand: 'ABM', adviser: 'Mrs. Cynthia Roxas', room: 'Bldg 1 · Rm 102', count: 38, cap: 45 },
                  { section: 'HUMSS 11-A', grade: 'Grade 11', strand: 'HUMSS', adviser: 'Mr. Dennis Santos', room: 'Bldg 1 · Rm 105', count: 45, cap: 45 },
                  { section: 'TVL-ICT 11-A', grade: 'Grade 11', strand: 'TVL-ICT', adviser: 'Engr. Ronald Cruz', room: 'ICT Lab 1', count: 35, cap: 40 },
                  { section: 'GAS 11-A', grade: 'Grade 11', strand: 'GAS', adviser: 'Ms. Elena Torres', room: 'Bldg 3 · Rm 301', count: 28, cap: 40 },
                ].map((sec) => (
                  <tr key={sec.section} className="hover:bg-bg3/50 transition-colors">
                    <td className="px-4 py-3 font-semibold text-text">{sec.section}</td>
                    <td className="px-4 py-3 text-text2">{sec.grade}</td>
                    <td className="px-4 py-3">
                      <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-accent/15 text-accent">
                        {sec.strand}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-text font-medium">{sec.adviser}</td>
                    <td className="px-4 py-3 text-text3">{sec.room}</td>
                    <td className="px-4 py-3 text-right tabular-nums text-text font-semibold">
                      {sec.count} / {sec.cap}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 5c. INSTITUTIONAL REPORTS (admin_reports.php)                 */}
      {/* ============================================================ */}
      {activeTab === 'reports' && (
        <div className="space-y-4">
          <div className="p-5 rounded-xl bg-bg2 border border-border">
            <h3 className="text-base font-bold text-text mb-1">Institutional Reports & DepEd LIS</h3>
            <p className="text-xs text-text2 mb-4">Official reports and enrollment records for Excelsior Polytechnic Academy.</p>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {[
                { title: 'DepEd School Form 1 (SF1 - School Register)', desc: 'Official master list of enrolled Senior High students with LRN & demographic records.' },
                { title: 'DepEd School Form 2 (SF2 - Daily Attendance)', desc: 'Monthly summary of learner attendance and absenteeism logs.' },
                { title: 'DepEd School Form 5 (SF5 - Report on Promotion)', desc: 'End-of-term promotional status, general averages, and academic honors.' },
                { title: 'DepEd School Form 10 (SF10 - Permanent Record)', desc: 'Learner individual permanent academic achievement record (formerly Form 137).' },
              ].map((rep, idx) => (
                <div key={idx} className="p-4 rounded-xl bg-bg3 border border-border flex flex-col justify-between space-y-3">
                  <div>
                    <div className="flex items-center gap-2 mb-1.5">
                      <RiFileChartLine className="size-4 text-accent shrink-0" />
                      <h4 className="text-xs font-bold text-text">{rep.title}</h4>
                    </div>
                    <p className="text-[11px] text-text3 leading-relaxed">{rep.desc}</p>
                  </div>
                  <div className="flex items-center gap-2 pt-2 border-t border-border/60">
                    <button
                      type="button"
                      className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-bg2 border border-border text-[11px] font-medium text-text hover:bg-bg4 cursor-pointer"
                    >
                      <RiDownload2Line className="size-3.5 text-accent" />
                      <span>Export (.xlsx)</span>
                    </button>
                    <button
                      type="button"
                      className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-bg2 border border-border text-[11px] font-medium text-text hover:bg-bg4 cursor-pointer"
                    >
                      <RiPrinterLine className="size-3.5 text-amber" />
                      <span>Print PDF</span>
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 5d. ANALYTICS & METRICS (admin_analytics.php)                 */}
      {/* ============================================================ */}
      {activeTab === 'analytics' && (
        <div className="space-y-4">
          <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
            <div>
              <h3 className="text-base font-bold text-text">Academy Analytics & Performance</h3>
              <p className="text-xs text-text2">Academic year 2026–2027 cross-departmental KPIs.</p>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div className="p-4 rounded-xl bg-bg3 border border-border">
                <span className="text-[10px] font-bold uppercase tracking-wider text-text3">Learner Pass Rate</span>
                <div className="text-2xl font-bold font-serif text-green mt-1">94.8%</div>
                <div className="text-[10px] text-text3 mt-0.5">+2.4% vs last semester</div>
              </div>
              <div className="p-4 rounded-xl bg-bg3 border border-border">
                <span className="text-[10px] font-bold uppercase tracking-wider text-text3">Completion Rate</span>
                <div className="text-2xl font-bold font-serif text-accent mt-1">98.2%</div>
                <div className="text-[10px] text-text3 mt-0.5">Senior High cohorts</div>
              </div>
              <div className="p-4 rounded-xl bg-bg3 border border-border">
                <span className="text-[10px] font-bold uppercase tracking-wider text-text3">Daily Attendance</span>
                <div className="text-2xl font-bold font-serif text-amber mt-1">92.6%</div>
                <div className="text-[10px] text-text3 mt-0.5">Blended & virtual</div>
              </div>
              <div className="p-4 rounded-xl bg-bg3 border border-border">
                <span className="text-[10px] font-bold uppercase tracking-wider text-text3">Faculty Utilization</span>
                <div className="text-2xl font-bold font-serif text-purple mt-1">87.5%</div>
                <div className="text-[10px] text-text3 mt-0.5">24 active teaching faculty</div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Screen: About (about.php) */}
      {activeTab === 'about' && (
        <AboutView role="admin" onTabChange={onTabChange} />
      )}

      {/* ============================================================ */}
      {/* 6. ADMIN PROFILE & SETTINGS (admin_profile.php / settings.php)*/}
      {/* ============================================================ */}
      {(activeTab === 'profile' || activeTab === 'settings') && (
        <div className="max-w-2xl p-6 rounded-xl bg-bg2 border border-border shadow-xs space-y-4">
          <h3 className="text-xl font-bold font-serif text-text">Administrator Credentials & Security</h3>
          <p className="text-xs text-text2">Super Administrator account settings.</p>

          {passUpdated && (
            <div className="p-3 rounded-lg bg-green-bg border border-green/30 text-xs text-green flex items-center gap-2">
              <RiCheckLine className="size-4" />
              <span>Admin password updated successfully!</span>
            </div>
          )}

          <div className="p-3 rounded-lg bg-bg3 border border-border text-xs space-y-1">
            <div className="text-text3 text-[10px] uppercase font-bold">Admin Account Email</div>
            <div className="font-semibold text-text">excelsiorpolyacad@gmail.com</div>
            <div className="text-text2">Institutional Control · System ID #ADM-001</div>
          </div>

          <form
            onSubmit={(e) => {
              e.preventDefault();
              if (newPass !== confirmPass) return;
              setPassUpdated(true);
              setCurrentPass('');
              setNewPass('');
              setConfirmPass('');
              setTimeout(() => setPassUpdated(false), 2500);
            }}
            className="space-y-3"
          >
            <div>
              <label className="block text-xs font-semibold text-text2 mb-1">Current Password *</label>
              <input
                type="password"
                placeholder="Enter current password"
                value={currentPass}
                onChange={(e) => setCurrentPass(e.target.value)}
                className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                required
              />
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">New Password *</label>
                <input
                  type="password"
                  placeholder="Min 8 characters"
                  value={newPass}
                  onChange={(e) => setNewPass(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                  required
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Confirm New Password *</label>
                <input
                  type="password"
                  placeholder="Confirm new password"
                  value={confirmPass}
                  onChange={(e) => setConfirmPass(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                  required
                />
              </div>
            </div>
            <div className="flex justify-end pt-2">
              <button
                type="submit"
                className="px-4 py-2 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer shadow-xs"
              >
                Update Password
              </button>
            </div>
          </form>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 1: ADD NEW STUDENT (admin_students.php lines 1130-1235) */}
      {/* ============================================================ */}
      {isAddStudentOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-xl max-h-[90vh] bg-bg2 rounded-2xl border border-border shadow-2xl overflow-y-auto p-6 space-y-4 text-xs">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <h4 className="text-base font-bold text-text">Add New Student</h4>
              <button
                type="button"
                onClick={() => setIsAddStudentOpen(false)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            <form onSubmit={handleAddStudentSubmit} className="space-y-3">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-text2 mb-1">First Name *</label>
                  <input
                    type="text"
                    placeholder="e.g. John Paolo"
                    value={newFirst}
                    onChange={(e) => setNewFirst(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-text2 mb-1">Middle Name</label>
                  <input
                    type="text"
                    placeholder="e.g. Santos"
                    value={newMiddle}
                    onChange={(e) => setNewMiddle(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-text2 mb-1">Last Name *</label>
                  <input
                    type="text"
                    placeholder="e.g. Garcia"
                    value={newLast}
                    onChange={(e) => setNewLast(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-text2 mb-1">Suffix</label>
                  <input
                    type="text"
                    placeholder="e.g. Jr., Sr., III"
                    value={newSuffix}
                    onChange={(e) => setNewSuffix(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-text2 mb-1">DepEd LRN (12-Digit) *</label>
                  <input
                    type="text"
                    placeholder="Enter Learner Reference Number"
                    value={newLrn}
                    onChange={(e) => setNewLrn(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text font-mono"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-text2 mb-1">Student ID *</label>
                  <input
                    type="text"
                    placeholder="e.g. 2026-000001"
                    value={newStudId}
                    onChange={(e) => setNewStudId(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text font-mono"
                  />
                </div>
              </div>

              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="block font-semibold text-text2 mb-1">Birthdate *</label>
                  <input
                    type="date"
                    value={newBirthdate}
                    onChange={(e) => setNewBirthdate(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  />
                </div>
                <div>
                  <label className="block font-semibold text-text2 mb-1">Gender *</label>
                  <select
                    value={newGender}
                    onChange={(e) => setNewGender(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  >
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-text2 mb-1">Contact Number *</label>
                  <input
                    type="text"
                    placeholder="e.g. 09123456789"
                    value={newContact}
                    onChange={(e) => setNewContact(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  />
                </div>
              </div>

              <div>
                <label className="block font-semibold text-text2 mb-1">Complete Address *</label>
                <textarea
                  rows={2}
                  placeholder="Complete Residential Address"
                  value={newAddress}
                  onChange={(e) => setNewAddress(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-text2 mb-1">Grade Level *</label>
                  <select
                    value={newGrade}
                    onChange={(e) => setNewGrade(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  >
                    <option value="Grade 11">Grade 11</option>
                    <option value="Grade 12">Grade 12</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-text2 mb-1">Section / Block *</label>
                  <select
                    value={newSection}
                    onChange={(e) => setNewSection(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  >
                    <option value="STEM 11-A">STEM 11-A</option>
                    <option value="STEM 11-B">STEM 11-B</option>
                    <option value="ABM 11-A">ABM 11-A</option>
                    <option value="HUMSS 11-A">HUMSS 11-A</option>
                    <option value="TVL-ICT 11-A">TVL-ICT 11-A</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block font-semibold text-text2 mb-1">Initial Password *</label>
                <input
                  type="text"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text font-mono"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-border">
                <button
                  type="button"
                  onClick={() => setIsAddStudentOpen(false)}
                  className="px-4 py-2 rounded-lg bg-bg3 text-text cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 rounded-xl bg-accent text-white font-semibold cursor-pointer shadow-xs"
                >
                  Add Student
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 2: CSV / EXCEL IMPORT (admin_students.php 1264-1275)    */}
      {/* ============================================================ */}
      {isCsvModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-lg bg-bg2 rounded-2xl border border-border shadow-2xl p-6 space-y-4 text-xs">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <h4 className="text-base font-bold text-text">📤 Import Students via CSV / Excel (.xlsx)</h4>
              <button
                type="button"
                onClick={() => setIsCsvModalOpen(false)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            <div className="p-3.5 rounded-xl bg-bg3 border border-border space-y-2">
              <strong className="block text-text">📋 Required Columns:</strong>
              <div className="flex flex-wrap gap-1.5 text-[10px]">
                <span className="px-2 py-0.5 rounded bg-bg2 border border-border font-mono">firstname</span>
                <span className="px-2 py-0.5 rounded bg-bg2 border border-border font-mono">lastname</span>
                <span className="px-2 py-0.5 rounded bg-bg2 border border-border font-mono">lrn</span>
                <span className="px-2 py-0.5 rounded bg-bg2 border border-border font-mono">student_id</span>
                <span className="px-2 py-0.5 rounded bg-bg2 border border-border font-mono">grade_level</span>
                <span className="px-2 py-0.5 rounded bg-bg2 border border-border font-mono">section</span>
              </div>
            </div>

            <div
              onClick={() => alert('File selected: DepEd_SHS_Batch_Enrollment_STEM11.xlsx (38 records)')}
              className="border-2 border-dashed border-border rounded-xl p-6 text-center cursor-pointer hover:border-green transition-colors"
            >
              <RiUpload2Line className="size-8 text-green mx-auto mb-2" />
              <span className="font-semibold text-text block">Click to upload spreadsheet file (.xlsx or .csv)</span>
              <span className="text-[11px] text-text3 mt-1 block">Parsed automatically via PhpSpreadsheet</span>
            </div>

            <div className="flex justify-end gap-2 pt-2 border-t border-border">
              <button
                type="button"
                onClick={() => setIsCsvModalOpen(false)}
                className="px-4 py-2 rounded-lg bg-bg3 text-text cursor-pointer"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={() => {
                  alert('Batch enrollment completed! 38 student accounts successfully created with DepEd LRNs.');
                  setIsCsvModalOpen(false);
                }}
                className="px-5 py-2 rounded-xl bg-green text-[#0e0f14] font-semibold cursor-pointer shadow-xs"
              >
                Ingest Spreadsheet
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 3: ADD SUBJECT (admin_subjects.php)                    */}
      {/* ============================================================ */}
      {isAddSubjectOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-md bg-bg2 rounded-2xl border border-border shadow-2xl p-6 space-y-4 text-xs">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <h4 className="text-base font-bold text-text">Add Curriculum Subject</h4>
              <button
                type="button"
                onClick={() => setIsAddSubjectOpen(false)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            <div className="space-y-3">
              <div>
                <label className="block font-semibold text-text2 mb-1">Subject Code *</label>
                <input
                  type="text"
                  placeholder="e.g. PRE-CALC-11"
                  value={subCode}
                  onChange={(e) => setSubCode(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text font-mono"
                />
              </div>
              <div>
                <label className="block font-semibold text-text2 mb-1">Subject Title *</label>
                <input
                  type="text"
                  placeholder="e.g. Pre-Calculus"
                  value={subTitle}
                  onChange={(e) => setSubTitle(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-text2 mb-1">Grade Level</label>
                  <select
                    value={subGrade}
                    onChange={(e) => setSubGrade(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  >
                    <option value="Grade 11">Grade 11</option>
                    <option value="Grade 12">Grade 12</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-text2 mb-1">Strand</label>
                  <select
                    value={subStrand}
                    onChange={(e) => setSubStrand(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  >
                    <option value="STEM">STEM</option>
                    <option value="ABM">ABM</option>
                    <option value="HUMSS">HUMSS</option>
                    <option value="Core">Core (All)</option>
                  </select>
                </div>
              </div>
            </div>

            <div className="flex justify-end gap-2 pt-2 border-t border-border">
              <button
                type="button"
                onClick={() => setIsAddSubjectOpen(false)}
                className="px-4 py-2 rounded-lg bg-bg3 text-text cursor-pointer"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={() => {
                  alert(`Subject "${subTitle || 'New Subject'}" successfully cataloged!`);
                  setIsAddSubjectOpen(false);
                }}
                className="px-5 py-2 rounded-xl bg-school-orange text-[#0e0f14] font-semibold cursor-pointer shadow-xs"
              >
                Save Subject
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 4: ADD TEACHER                                         */}
      {/* ============================================================ */}
      {isAddTeacherOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-md bg-bg2 rounded-2xl border border-border shadow-2xl p-6 space-y-4 text-xs">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <h4 className="text-base font-bold text-text">Register Faculty Member</h4>
              <button
                type="button"
                onClick={() => setIsAddTeacherOpen(false)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            <div className="space-y-3">
              <div>
                <label className="block font-semibold text-text2 mb-1">Full Name *</label>
                <input
                  type="text"
                  placeholder="e.g. Engr. Allan Bautista"
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                />
              </div>
              <div>
                <label className="block font-semibold text-text2 mb-1">DepEd / Gmail Address *</label>
                <input
                  type="email"
                  placeholder="e.g. allan.bautista@deped.gov.ph"
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                />
              </div>
              <div>
                <label className="block font-semibold text-text2 mb-1">PRC License Number *</label>
                <input
                  type="text"
                  placeholder="e.g. PRC-0899120"
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text font-mono"
                />
              </div>
            </div>

            <div className="flex justify-end gap-2 pt-2 border-t border-border">
              <button
                type="button"
                onClick={() => setIsAddTeacherOpen(false)}
                className="px-4 py-2 rounded-lg bg-bg3 text-text cursor-pointer"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={() => {
                  alert('Faculty member onboarded and verified!');
                  setIsAddTeacherOpen(false);
                }}
                className="px-5 py-2 rounded-xl bg-green text-[#0e0f14] font-semibold cursor-pointer shadow-xs"
              >
                Save & Verify
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
