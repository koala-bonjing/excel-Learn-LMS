import { useState } from 'react';
import type { FacultyApproval, StudentDirectoryItem } from '@/types';
import {
  INITIAL_FACULTY_APPROVALS,
  SHS_STRANDS,
  STUDENT_DIRECTORY,
} from '@/data/mockData';
import {
  RiGroupLine,
  RiShieldCheckLine,
  RiBarChartLine,
  RiCheckLine,
  RiCloseLine,
  RiSearchLine,
  RiFileExcel2Line,
  RiUserFollowLine,
  RiAddLine,
  RiUpload2Line,
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

  const pendingApprovals = approvals.filter((a) => a.status === 'pending');

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
      {/* 1. EXECUTIVE DASHBOARD (admin/index.php)                     */}
      {/* ============================================================ */}
      {activeTab === 'dashboard' && (
        <>
          {/* 4 KPI Stat Cards */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Total Students</span>
                <div className="p-2 rounded-lg bg-accent/10 text-accent">
                  <RiGroupLine className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">{students.length + 479}</div>
              <span className="text-xs text-accent mt-1 inline-block font-medium">100% LRN Registered</span>
            </div>

            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Verified Faculty</span>
                <div className="p-2 rounded-lg bg-green/10 text-green">
                  <RiUserFollowLine className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">24</div>
              <span className="text-xs text-green mt-1 inline-block font-medium">DepEd PRC Licensed</span>
            </div>

            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Pending Approvals</span>
                <div className="p-2 rounded-lg bg-amber/10 text-amber">
                  <RiShieldCheckLine className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">
                {pendingApprovals.length}
              </div>
              <span className="text-xs text-amber mt-1 inline-block font-medium">Teacher Onboarding</span>
            </div>

            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Active Strands</span>
                <div className="p-2 rounded-lg bg-purple/10 text-purple">
                  <RiBarChartLine className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">5</div>
              <span className="text-xs text-purple mt-1 inline-block font-medium">13 Total Sections</span>
            </div>
          </div>

          {/* Quick Actions Toolbar */}
          <div className="p-4 rounded-xl bg-bg2 border border-border flex flex-wrap items-center justify-between gap-3">
            <span className="text-xs font-bold text-text3 uppercase tracking-wider">Administrator Operations:</span>
            <div className="flex flex-wrap items-center gap-2">
              <button
                type="button"
                onClick={() => setIsAddStudentOpen(true)}
                className="px-3 py-1.5 rounded-lg bg-bg3 hover:bg-bg4 text-text text-xs font-semibold border border-border flex items-center gap-1.5 cursor-pointer"
              >
                <RiAddLine className="size-3.5 text-accent" />
                <span>+ Add Student</span>
              </button>
              <button
                type="button"
                onClick={() => setIsCsvModalOpen(true)}
                className="px-3 py-1.5 rounded-lg bg-bg3 hover:bg-bg4 text-text text-xs font-semibold border border-border flex items-center gap-1.5 cursor-pointer"
              >
                <RiFileExcel2Line className="size-3.5 text-green" />
                <span>Import CSV / Excel</span>
              </button>
              <button
                type="button"
                onClick={() => onTabChange('teachers')}
                className="px-3 py-1.5 rounded-lg bg-purple text-white text-xs font-semibold flex items-center gap-1.5 cursor-pointer hover:bg-purple/90 shadow-xs"
              >
                <RiShieldCheckLine className="size-3.5" />
                <span>Review Approvals ({pendingApprovals.length})</span>
              </button>
            </div>
          </div>

          {/* Pending Faculty Approvals Quick Feed */}
          <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-base font-bold text-text font-serif">Pending Faculty Verification Queue</h4>
                <p className="text-xs text-text2">PRC Licensed teachers awaiting portal access approval.</p>
              </div>
              <button
                type="button"
                onClick={() => onTabChange('teachers')}
                className="text-xs text-accent hover:underline font-semibold"
              >
                View Directory &rarr;
              </button>
            </div>

            <div className="grid grid-cols-1 gap-2.5">
              {pendingApprovals.map((fac) => (
                <div key={fac.id} className="p-3.5 rounded-xl bg-bg3 border border-border flex items-center justify-between">
                  <div>
                    <div className="text-xs font-bold text-text">{fac.name}</div>
                    <div className="text-[11px] text-text3">{fac.department} · {fac.prcLicense}</div>
                  </div>
                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={() => handleRejectFaculty(fac.id)}
                      className="px-2.5 py-1 rounded-lg border border-red/40 text-red text-xs hover:bg-red/10 cursor-pointer"
                    >
                      Reject
                    </button>
                    <button
                      type="button"
                      onClick={() => handleApproveFaculty(fac.id)}
                      className="px-3 py-1 rounded-lg bg-green text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
                    >
                      Approve
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </>
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
