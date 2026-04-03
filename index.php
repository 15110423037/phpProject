<?php
header('Content-Type: text/html; charset=UTF-8');

$tasksFile = 'tasks.json';

function loadTasks() {
    global $tasksFile;
    if (!file_exists($tasksFile)) {
        file_put_contents($tasksFile, '[]');
    }
    return json_decode(file_get_contents($tasksFile), true) ?: [];
}

function saveTasks($tasks) {
    global $tasksFile;
    file_put_contents($tasksFile, json_encode($tasks, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && isset($_GET['api'])) {
    if ($_GET['api'] === 'tasks') {
        echo json_encode(loadTasks());
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'save') {
        saveTasks($input['tasks']);
        echo json_encode(['success' => true]);
        exit;
    }
}

$tasks = loadTasks();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MinimalTodo</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header>
        <h1>MinimalTodo</h1>
        <div class="view-toggle">
            <button class="view-btn active" data-view="kanban">Kanban</button>
            <button class="view-btn" data-view="calendar">Calendar</button>
        </div>
    </header>
    
    <main>
        <div class="kanban-view active" id="kanbanView">
            <div class="column" data-status="todo">
                <div class="column-header">
                    <span>Todo</span>
                    <span class="column-count" id="todoCount">0</span>
                </div>
                <div class="task-list" id="todoList"></div>
                <button class="add-task-btn" data-status="todo">+ Add Task</button>
            </div>
            
            <div class="column" data-status="inprogress">
                <div class="column-header">
                    <span>In Progress</span>
                    <span class="column-count" id="inprogressCount">0</span>
                </div>
                <div class="task-list" id="inprogressList"></div>
                <button class="add-task-btn" data-status="inprogress">+ Add Task</button>
            </div>
            
            <div class="column" data-status="done">
                <div class="column-header">
                    <span>Done</span>
                    <span class="column-count" id="doneCount">0</span>
                </div>
                <div class="task-list" id="doneList"></div>
                <button class="add-task-btn" data-status="done">+ Add Task</button>
            </div>
        </div>
        
        <div class="calendar-view" id="calendarView">
            <div class="calendar-header">
                <div class="calendar-nav">
                    <button id="prevMonth">&lt;</button>
                    <span class="calendar-month" id="calendarMonth"></span>
                    <button id="nextMonth">&gt;</button>
                </div>
                <button class="btn btn-secondary" id="todayBtn">Today</button>
            </div>
            <div class="calendar-grid" id="calendarGrid"></div>
        </div>
    </main>
    
    <div class="modal-overlay" id="taskModal">
        <div class="modal">
            <h2 class="modal-title" id="modalTitle">Add Task</h2>
            
            <div class="form-group">
                <label>Title</label>
                <input type="text" id="taskTitle" placeholder="Enter task title...">
            </div>
            
            <div class="form-group">
                <label>Due Date</label>
                <input type="date" id="taskDueDate">
            </div>
            
            <div class="form-group">
                <label>Subtasks</label>
                <div class="subtasks-list" id="subtasksList"></div>
                <button class="add-subtask-btn" id="addSubtaskBtn">+ Add Subtask</button>
            </div>
            
            <div class="form-group">
                <label>Info</label>
                <div class="info-toolbar">
                    <button data-command="bold" title="Bold"><b>B</b></button>
                    <button data-command="italic" title="Italic"><i>I</i></button>
                    <button data-command="underline" title="Underline"><u>U</u></button>
                    <button data-command="strikethrough" title="Strikethrough"><s>S</s></button>
                    <button data-command="insertUnorderedList" title="Bullet List">•</button>
                    <button data-command="insertOrderedList" title="Numbered List">1.</button>
                </div>
                <div class="info-editor" id="infoEditor" contenteditable="true"></div>
            </div>
            
            <div class="modal-actions">
                <button class="btn btn-danger" id="deleteTaskBtn" style="display:none;">Delete</button>
                <button class="btn btn-secondary" id="cancelBtn">Cancel</button>
                <button class="btn btn-primary" id="saveTaskBtn">Save</button>
            </div>
        </div>
    </div>
    
    <script>
        let tasks = [];
        let currentView = 'kanban';
        let editingTaskId = null;
        let currentTaskStatus = 'todo';
        let calendarDate = new Date();
        
        const modal = document.getElementById('taskModal');
        const subtasksList = document.getElementById('subtasksList');
        
        function generateId() {
            return Date.now().toString(36) + Math.random().toString(36).substr(2);
        }
        
        async function loadTasks() {
            const res = await fetch('?api=tasks');
            tasks = await res.json();
            render();
        }
        
        async function saveTasks() {
            await fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'save', tasks})
            });
        }
        
        function render() {
            renderKanban();
            renderCalendar();
        }
        
        function renderKanban() {
            const columns = {
                todo: document.getElementById('todoList'),
                inprogress: document.getElementById('inprogressList'),
                done: document.getElementById('doneList')
            };
            
            Object.keys(columns).forEach(status => {
                columns[status].innerHTML = '';
            });
            
            const counts = {todo: 0, inprogress: 0, done: 0};
            
            tasks.forEach(task => {
                const card = createTaskCard(task);
                if (columns[task.status]) {
                    columns[task.status].appendChild(card);
                    counts[task.status]++;
                }
            });
            
            document.getElementById('todoCount').textContent = counts.todo;
            document.getElementById('inprogressCount').textContent = counts.inprogress;
            document.getElementById('doneCount').textContent = counts.done;
        }
        
        function createTaskCard(task) {
            const card = document.createElement('div');
            card.className = 'task-card';
            card.draggable = true;
            card.dataset.id = task.id;
            
            let dueDateClass = '';
            if (task.dueDate) {
                const due = new Date(task.dueDate);
                const today = new Date();
                today.setHours(0,0,0,0);
                if (due < today) dueDateClass = 'overdue';
                else if ((due - today) <= 3 * 24 * 60 * 60 * 1000) dueDateClass = 'soon';
            }
            
            let subtasksHtml = '';
            if (task.subtasks && task.subtasks.length > 0) {
                const allDone = task.subtasks.every(s => s.done);
                subtasksHtml = `<div class="task-card-subtasks ${allDone ? 'complete' : ''}">`;
                task.subtasks.forEach(subtask => {
                    subtasksHtml += `
                        <div class="task-card-subtask ${subtask.done ? 'done' : ''}">
                            <input type="checkbox" ${subtask.done ? 'checked' : ''} data-subtask-id="${subtask.id}">
                            <span>${escapeHtml(subtask.title)}</span>
                        </div>
                    `;
                });
                subtasksHtml += '</div>';
            }
            
            card.innerHTML = `
                <div class="task-card-title">${escapeHtml(task.title)}</div>
                ${subtasksHtml}
                <div class="task-card-meta">
                    ${task.dueDate ? `<span class="task-card-due ${dueDateClass}">📅 ${formatDate(task.dueDate)}</span>` : ''}
                </div>
            `;
            
            card.addEventListener('click', (e) => {
                if (e.target.type === 'checkbox') {
                    e.stopPropagation();
                    const subtaskId = e.target.dataset.subtaskId;
                    const subtask = task.subtasks.find(s => s.id === subtaskId);
                    if (subtask) {
                        subtask.done = e.target.checked;
                        saveTasks().then(render);
                    }
                } else {
                    openTaskModal(task);
                }
            });
            
            card.addEventListener('dragstart', (e) => {
                card.classList.add('dragging');
                e.dataTransfer.setData('text/plain', task.id);
            });
            
            card.addEventListener('dragend', () => {
                card.classList.remove('dragging');
            });
            
            return card;
        }
        
        document.querySelectorAll('.column').forEach(column => {
            column.addEventListener('dragover', (e) => {
                e.preventDefault();
            });
            
            column.addEventListener('drop', (e) => {
                e.preventDefault();
                const taskId = e.dataTransfer.getData('text/plain');
                const newStatus = column.dataset.status;
                
                const task = tasks.find(t => t.id === taskId);
                if (task && task.status !== newStatus) {
                    task.status = newStatus;
                    saveTasks().then(render);
                }
            });
        });
        
        function renderCalendar() {
            const grid = document.getElementById('calendarGrid');
            const monthLabel = document.getElementById('calendarMonth');
            
            const year = calendarDate.getFullYear();
            const month = calendarDate.getMonth();
            
            monthLabel.textContent = new Date(year, month).toLocaleDateString('en-US', {month: 'long', year: 'numeric'});
            
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());
            
            const today = new Date();
            today.setHours(0,0,0,0);
            
            grid.innerHTML = '';
            
            ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
                const header = document.createElement('div');
                header.className = 'calendar-day-header';
                header.textContent = day;
                grid.appendChild(header);
            });
            
            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                
                const cell = document.createElement('div');
                cell.className = 'calendar-day';
                
                if (date.getMonth() !== month) {
                    cell.classList.add('other-month');
                }
                
                if (date.getTime() === today.getTime()) {
                    cell.classList.add('today');
                }
                
                const dayNum = document.createElement('div');
                dayNum.className = 'calendar-day-number';
                dayNum.textContent = date.getDate();
                cell.appendChild(dayNum);
                
                const dateStr = date.toISOString().split('T')[0];
                const dayTasks = tasks.filter(t => t.dueDate === dateStr);
                
                dayTasks.forEach(task => {
                    const taskEl = document.createElement('div');
                    taskEl.className = 'calendar-task' + (task.status === 'done' ? ' done' : '');
                    taskEl.textContent = task.title;
                    cell.appendChild(taskEl);
                });
                
                cell.addEventListener('click', (e) => {
                    if (e.target === cell || e.target === dayNum) {
                        openTaskModal(null, dateStr);
                    }
                });
                
                grid.appendChild(cell);
            }
        }
        
        document.getElementById('prevMonth').addEventListener('click', () => {
            calendarDate.setMonth(calendarDate.getMonth() - 1);
            renderCalendar();
        });
        
        document.getElementById('nextMonth').addEventListener('click', () => {
            calendarDate.setMonth(calendarDate.getMonth() + 1);
            renderCalendar();
        });
        
        document.getElementById('todayBtn').addEventListener('click', () => {
            calendarDate = new Date();
            renderCalendar();
        });
        
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const view = btn.dataset.view;
                document.getElementById('kanbanView').classList.toggle('active', view === 'kanban');
                document.getElementById('calendarView').classList.toggle('active', view === 'calendar');
                currentView = view;
                
                if (view === 'calendar') renderCalendar();
            });
        });
        
        document.querySelectorAll('.add-task-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                openTaskModal(null, null, btn.dataset.status);
            });
        });
        
        function openTaskModal(task = null, date = null, status = 'todo') {
            editingTaskId = task ? task.id : null;
            currentTaskStatus = status;
            document.getElementById('modalTitle').textContent = task ? 'Edit Task' : 'Add Task';
            document.getElementById('taskTitle').value = task ? task.title : '';
            document.getElementById('taskDueDate').value = task ? task.dueDate : (date || '');
            document.getElementById('infoEditor').innerHTML = task ? task.info : '';
            document.getElementById('deleteTaskBtn').style.display = task ? 'block' : 'none';
            
            renderSubtasks(task ? task.subtasks : []);
            
            modal.classList.add('active');
        }
        
        function renderSubtasks(subtasks = []) {
            subtasksList.innerHTML = '';
            subtasks.forEach((subtask, index) => {
                const item = document.createElement('div');
                item.className = 'subtask-item';
                item.dataset.id = subtask.id;
                item.innerHTML = `
                    <input type="checkbox" ${subtask.done ? 'checked' : ''} data-index="${index}">
                    <input type="text" value="${escapeHtml(subtask.title)}" data-index="${index}">
                    <button class="subtask-remove" data-index="${index}">×</button>
                `;
                subtasksList.appendChild(item);
            });
        }
        
        document.getElementById('addSubtaskBtn').addEventListener('click', () => {
            const subtasks = getSubtasksFromForm();
            subtasks.push({id: generateId(), title: '', done: false});
            renderSubtasks(subtasks);
        });
        
        subtasksList.addEventListener('click', (e) => {
            if (e.target.classList.contains('subtask-remove')) {
                const index = parseInt(e.target.dataset.index);
                const subtasks = getSubtasksFromForm();
                subtasks.splice(index, 1);
                renderSubtasks(subtasks);
            }
        });
        
        function getSubtasksFromForm() {
            const items = subtasksList.querySelectorAll('.subtask-item');
            const subtasks = [];
            items.forEach((item, index) => {
                const checkbox = item.querySelector('input[type="checkbox"]');
                const text = item.querySelector('input[type="text"]');
                if (text.value.trim()) {
                    subtasks.push({
                        id: item.dataset.id || generateId(),
                        title: text.value.trim(),
                        done: checkbox.checked
                    });
                }
            });
            return subtasks;
        }
        
        document.querySelectorAll('.info-toolbar button').forEach(btn => {
            btn.addEventListener('click', () => {
                document.execCommand(btn.dataset.command, false, null);
                document.getElementById('infoEditor').focus();
            });
        });
        
        document.getElementById('cancelBtn').addEventListener('click', () => {
            modal.classList.remove('active');
        });
        
        document.getElementById('saveTaskBtn').addEventListener('click', () => {
            const title = document.getElementById('taskTitle').value.trim();
            if (!title) {
                alert('Please enter a title');
                return;
            }
            
            const dueDate = document.getElementById('taskDueDate').value;
            const info = document.getElementById('infoEditor').innerHTML;
            const subtasks = getSubtasksFromForm();
            
            if (editingTaskId) {
                const task = tasks.find(t => t.id === editingTaskId);
                if (task) {
                    task.title = title;
                    task.dueDate = dueDate;
                    task.info = info;
                    task.subtasks = subtasks;
                }
            } else {
                tasks.push({
                    id: generateId(),
                    title,
                    status: currentTaskStatus,
                    dueDate,
                    info,
                    subtasks,
                    createdAt: new Date().toISOString()
                });
            }
            
            saveTasks().then(() => {
                modal.classList.remove('active');
                render();
            });
        });
        
        document.getElementById('deleteTaskBtn').addEventListener('click', () => {
            if (confirm('Delete this task?')) {
                tasks = tasks.filter(t => t.id !== editingTaskId);
                saveTasks().then(() => {
                    modal.classList.remove('active');
                    render();
                });
            }
        });
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
        }
        
        loadTasks();
    </script>
</body>
</html>
