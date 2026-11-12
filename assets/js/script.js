// ===== SHARED STATE (localStorage-backed) =====
const DEFAULT_STATE = {
  courses: [
    {id:1, title:'Introduction to Science', desc:'Explore the fundamentals of scientific thinking.',cat:'Science',grade:'Grade 7',thumb:'t1',icon:'🔬'},
    {id:2, title:'Mathematics — Algebra Basics', desc:'Master variables, equations and expressions.',cat:'Mathematics',grade:'Grade 8',thumb:'t2',icon:'📐'},
    {id:3, title:'English Literature', desc:'Reading, comprehension and creative writing.',cat:'English',grade:'Grade 9',thumb:'t3',icon:'📖'},
  ],
  lessons: [
    {id:1,courseId:1,title:'The Scientific Method',type:'text',duration:20,content:'Science begins with observation...'},
    {id:2,courseId:1,title:'Introduction to Cells',type:'video',videoUrl:'https://youtube.com/watch?v=example',duration:15},
    {id:3,courseId:1,title:'Lab Safety Overview',type:'presentation',slides:8,presUrl:'https://docs.google.com/...'},
    {id:4,courseId:2,title:'What are Variables?',type:'text',duration:18,content:'In algebra, a variable is...'},
    {id:5,courseId:2,title:'Solving Linear Equations',type:'video',videoUrl:'https://youtube.com/watch?v=example2',duration:22},
    {id:6,courseId:3,title:'Reading Comprehension Strategies',type:'text',duration:25,content:'Active reading involves...'},
    {id:7,courseId:3,title:'Elements of a Short Story',type:'presentation',slides:12,presUrl:'https://canva.com/...'},
  ],
  quizzes: [
    {id:1,courseId:1,lessonId:1,title:'Scientific Method Quiz',timeLimit:10,questions:[{q:'What is the first step of the scientific method?',choices:['Observation','Hypothesis','Conclusion','Experiment'],correct:0}]},
    {id:2,courseId:2,lessonId:4,title:'Variables Check-in',timeLimit:5,questions:[{q:'What does a variable represent?',choices:['A number','An unknown value','A formula','A graph'],correct:1}]},
    {id:3,courseId:1,lessonId:null,title:'Mid-Unit Science Test',timeLimit:20,questions:[{q:'Which organelle is the powerhouse of the cell?',choices:['Nucleus','Mitochondria','Ribosome','Cell Wall'],correct:1}]},
  ],
  students: [
    {name:'Ana Santos',course:'Introduction to Science',progress:75,quizAvg:88},
    {name:'Juan dela Cruz',course:'Mathematics — Algebra Basics',progress:60,quizAvg:72},
    {name:'Maria Garcia',course:'English Literature',progress:90,quizAvg:95},
    {name:'Carlo Reyes',course:'Introduction to Science',progress:45,quizAvg:68},
    {name:'Liza Mendoza',course:'Mathematics — Algebra Basics',progress:82,quizAvg:79},
    {name:'Paolo Cruz',course:'English Literature',progress:55,quizAvg:84},
  ]
};

function loadState() {
  try {
    const saved = localStorage.getItem('lms_state');
    if (saved) return JSON.parse(saved);
  } catch(e) {}
  return JSON.parse(JSON.stringify(DEFAULT_STATE));
}

function saveState() {
  try { localStorage.setItem('lms_state', JSON.stringify(state)); } catch(e) {}
}

const state = loadState();

// ===== SIDEBAR TOGGLE =====
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}

// ===== THEME =====
function toggleTheme() {
  document.body.classList.toggle('light');
  const isLight = document.body.classList.contains('light');
  localStorage.setItem('theme', isLight ? 'light' : 'dark');
  const btn = document.getElementById('themeToggle');
  if (btn) {
    document.getElementById('theme-icon').textContent = isLight ? '☀️' : '🌙';
    document.getElementById('theme-text').textContent = isLight ? 'Light Mode' : 'Dark Mode';
  }
}
function initTheme() {
  const saved = localStorage.getItem('theme');
  if (saved === 'light') {
    document.body.classList.add('light');
    const btn = document.getElementById('themeToggle');
    if (btn) {
      document.getElementById('theme-icon').textContent = '☀️';
      document.getElementById('theme-text').textContent = 'Light Mode';
    }
  }
}

// ===== TOAST =====
function toast(msg, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span class="toast-icon">${type === 'success' ? '✓' : '✕'}</span>${msg}`;
  container.appendChild(t);
  setTimeout(() => t.remove(), 3000);
}

// ===== HELPERS =====
function typeIcon(type) { return type === 'video' ? '🎬' : type === 'presentation' ? '📊' : '📄'; }
function typeBadge(type) {
  if (type === 'video') return '<span class="badge badge-amber">🎬 Video</span>';
  if (type === 'presentation') return '<span class="badge badge-purple">📊 Slides</span>';
  return '<span class="badge badge-blue">📄 Text</span>';
}

function getCourseById(id) { return state.courses.find(c => c.id == id); }
function getLessonsByCourse(courseId) { return state.lessons.filter(l => l.courseId == courseId); }
function getQuizzesByCourse(courseId) { return state.quizzes.filter(q => q.courseId == courseId); }

// ===== MODALS =====
function openModal(type) {
  populateCourseSelects();
  document.getElementById('modal-' + type).classList.add('open');
}
function closeModal(type) {
  document.getElementById('modal-' + type).classList.remove('open');
}

function populateCourseSelects() {
  ['l-course', 'q-course'].forEach(id => {
    const sel = document.getElementById(id);
    if (!sel) return;
    sel.innerHTML = state.courses.map(c => `<option value="${c.id}">${c.title}</option>`).join('');
  });
  const qCourse = document.getElementById('q-course');
  if (qCourse) {
    populateLessonSelect(qCourse.value || state.courses[0]?.id);
    qCourse.onchange = function () { populateLessonSelect(this.value); };
  }
}

function populateLessonSelect(courseId) {
  const sel = document.getElementById('q-lesson');
  if (!sel) return;
  const courseLessons = state.lessons.filter(l => l.courseId == courseId);
  sel.innerHTML = '<option value="">— Standalone quiz —</option>' + courseLessons.map(l => `<option value="${l.id}">${l.title}</option>`).join('');
}

// ===== LESSON TYPE =====
let currentLessonType = 'text';
function selectLessonType(type, el) {
  currentLessonType = type;
  document.querySelectorAll('.type-opt').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('lesson-text-fields').style.display = type === 'text' ? '' : 'none';
  document.getElementById('lesson-video-fields').style.display = type === 'video' ? '' : 'none';
  document.getElementById('lesson-pres-fields').style.display = type === 'presentation' ? '' : 'none';
}

function previewVideo(url) {
  const pEl = document.getElementById('video-preview');
  const uEl = document.getElementById('video-preview-url');
  if (!pEl) return;
  if (url && url.length > 5) {
    pEl.style.display = 'flex';
    uEl.textContent = url.length > 45 ? url.substring(0, 45) + '...' : url;
  } else {
    pEl.style.display = 'none';
  }
}

// ===== SAVE COURSE =====
function saveCourse() {
  const title = document.getElementById('c-title').value.trim();
  if (!title) { toast('Please enter a course title', 'error'); return; }
  const thumbs = ['t1', 't2', 't3', 't4'];
  const icons = ['🔬', '📐', '📖', '🎨', '💻', '🌍', '🎵'];
  const newCourse = {
    id: Date.now(),
    title,
    desc: document.getElementById('c-desc').value.trim() || 'No description.',
    cat: document.getElementById('c-cat').value,
    grade: document.getElementById('c-grade').value,
    thumb: thumbs[state.courses.length % 4],
    icon: icons[state.courses.length % 7],
  };
  state.courses.push(newCourse);
  saveState();
  closeModal('course');
  document.getElementById('c-title').value = '';
  document.getElementById('c-desc').value = '';
  toast('Course created!', 'success');
  if (typeof renderPage === 'function') renderPage();
}

// ===== SAVE LESSON =====
function saveLesson() {
  const title = document.getElementById('l-title').value.trim();
  const courseId = parseInt(document.getElementById('l-course').value);
  if (!title) { toast('Please enter a lesson title', 'error'); return; }
  const lesson = { id: Date.now(), courseId, title, type: currentLessonType };
  if (currentLessonType === 'text') {
    lesson.content = document.getElementById('l-content').value;
    lesson.duration = parseInt(document.getElementById('l-duration').value) || 15;
  } else if (currentLessonType === 'video') {
    lesson.videoUrl = document.getElementById('l-video-url').value;
    lesson.duration = 0;
    lesson.desc = document.getElementById('l-video-desc').value;
  } else {
    lesson.presUrl = document.getElementById('l-pres-url').value;
    lesson.slides = parseInt(document.getElementById('l-slides').value) || 10;
  }
  state.lessons.push(lesson);
  saveState();
  const hasQuiz = document.getElementById('l-has-quiz').checked;
  closeModal('lesson');
  document.getElementById('l-title').value = '';
  const lc = document.getElementById('l-content');
  if (lc) lc.value = '';
  toast('Lesson saved!', 'success');
  if (typeof renderPage === 'function') renderPage();
  if (hasQuiz) setTimeout(() => openQuizModal(lesson.id), 300);
}

// ===== QUIZ BUILDER =====
let questionCount = 0;
function openQuizModal(lessonId) {
  populateCourseSelects();
  questionCount = 0;
  const container = document.getElementById('quiz-questions-container');
  if (container) container.innerHTML = '';
  if (lessonId) {
    const lesson = state.lessons.find(l => l.id === lessonId);
    if (lesson) {
      const qc = document.getElementById('q-course');
      if (qc) qc.value = lesson.courseId;
      populateLessonSelect(lesson.courseId);
      const ql = document.getElementById('q-lesson');
      if (ql) ql.value = lessonId;
      const qt = document.getElementById('q-title');
      if (qt) qt.value = 'Quiz: ' + lesson.title;
    }
  } else {
    const qt = document.getElementById('q-title');
    if (qt) qt.value = '';
  }
  addQuestion();
  openModal('quiz');
}

function addQuestion() {
  questionCount++;
  const qNum = questionCount;
  const container = document.getElementById('quiz-questions-container');
  if (!container) return;
  const div = document.createElement('div');
  div.className = 'quiz-question';
  div.id = 'question-' + qNum;
  div.innerHTML = `
    <div class="quiz-question-header">
      <div class="question-num">${qNum}</div>
      <input type="text" placeholder="Enter your question..." style="flex:1" id="q-text-${qNum}"/>
      ${qNum > 1 ? `<div class="icon-btn danger" onclick="removeQuestion(${qNum})">✕</div>` : ''}
    </div>
    <div class="choices-list" id="choices-${qNum}">
      <div class="choice-row"><input type="radio" name="correct-${qNum}" value="0" class="choice-radio" checked/><input type="text" class="choice-input" placeholder="Choice A" /></div>
      <div class="choice-row"><input type="radio" name="correct-${qNum}" value="1" class="choice-radio"/><input type="text" class="choice-input" placeholder="Choice B" /></div>
      <div class="choice-row"><input type="radio" name="correct-${qNum}" value="2" class="choice-radio"/><input type="text" class="choice-input" placeholder="Choice C" /></div>
    </div>
    <button class="add-choice-btn" onclick="addChoice(${qNum})">+ Add another choice</button>
    <div style="font-size:11px;color:var(--text3);margin-top:8px">● Select the radio button next to the correct answer</div>
  `;
  container.appendChild(div);
}

function addChoice(qNum) {
  const list = document.getElementById('choices-' + qNum);
  const count = list.children.length;
  const row = document.createElement('div');
  row.className = 'choice-row';
  row.innerHTML = `<input type="radio" name="correct-${qNum}" value="${count}" class="choice-radio"/><input type="text" class="choice-input" placeholder="Choice ${String.fromCharCode(65 + count)}" />`;
  list.appendChild(row);
}

function removeQuestion(qNum) {
  const el = document.getElementById('question-' + qNum);
  if (el) el.remove();
}

function saveQuiz() {
  const title = document.getElementById('q-title').value.trim();
  const courseId = parseInt(document.getElementById('q-course').value);
  if (!title) { toast('Please enter a quiz title', 'error'); return; }
  const lessonIdVal = document.getElementById('q-lesson').value;
  const lessonId = lessonIdVal ? parseInt(lessonIdVal) : null;
  const timeLimit = parseInt(document.getElementById('q-time').value) || 0;
  const questions = [];
  document.querySelectorAll('.quiz-question').forEach(qEl => {
    const id = qEl.id.replace('question-', '');
    const qText = document.getElementById('q-text-' + id)?.value.trim();
    if (!qText) return;
    const choices = [];
    qEl.querySelectorAll('.choice-input').forEach(inp => choices.push(inp.value.trim() || 'Option'));
    const correctEl = qEl.querySelector(`input[name="correct-${id}"]:checked`);
    const correct = correctEl ? parseInt(correctEl.value) : 0;
    questions.push({ q: qText, choices, correct });
  });
  if (!questions.length) { toast('Add at least one question', 'error'); return; }
  const quiz = { id: Date.now(), courseId, lessonId, title, timeLimit, questions };
  state.quizzes.push(quiz);
  saveState();
  closeModal('quiz');
  toast('Quiz saved with ' + questions.length + ' question(s)!', 'success');
  if (typeof renderPage === 'function') renderPage();
}

// ===== DELETE =====
function deleteLesson(id) {
  if (!confirm('Delete this lesson?')) return;
  const idx = state.lessons.findIndex(l => l.id === id);
  if (idx !== -1) state.lessons.splice(idx, 1);
  saveState();
  toast('Lesson deleted', 'success');
  if (typeof renderPage === 'function') renderPage();
}

function deleteQuiz(id) {
  if (!confirm('Delete this quiz?')) return;
  const idx = state.quizzes.findIndex(q => q.id === id);
  if (idx !== -1) state.quizzes.splice(idx, 1);
  saveState();
  toast('Quiz deleted', 'success');
  if (typeof renderPage === 'function') renderPage();
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', function () {
  initTheme();
  // Close modals on overlay click
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
      if (e.target === this) this.classList.remove('open');
    });
  });
  // Sidebar overlay click
  const so = document.getElementById('sidebar-overlay');
  if (so) so.addEventListener('click', closeSidebar);
  // Update badges
  const coursesBadge = document.getElementById('courses-badge');
  if (coursesBadge) coursesBadge.textContent = state.courses.length;
});
