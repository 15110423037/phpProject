# Minimal PHP Todo App - Specification

## Project Overview
- **Project Name**: MinimalTodo
- **Type**: Single-page PHP web application
- **Core Functionality**: A simple task manager with Kanban board, calendar view, subtasks, and rich text notes
- **Target Users**: Beginners, minimalists who want a self-hosted task tracker

## Data Storage
- File-based JSON storage (`tasks.json`)
- All data stored locally on PHP server
- No database required

## UI/UX Specification

### Layout Structure
- **Header**: App title + View toggle buttons (Kanban | Calendar)
- **Main Content**: Switches between Kanban and Calendar views
- **Task Modal**: Popup form for adding/editing tasks

### Visual Design
- **Color Palette**:
  - Background: `#f5f5f5` (light gray)
  - Cards: `#ffffff` (white)
  - Primary: `#3b82f6` (blue)
  - Success: `#22c55e` (green)
  - Warning: `#f59e0b` (amber)
  - Danger: `#ef4444` (red)
  - Text: `#1f2937` (dark gray)
  - Border: `#e5e7eb` (light border)
  
- **Typography**:
  - Font: System sans-serif
  - Headings: 18px bold
  - Body: 14px regular
  
- **Spacing**:
  - Card padding: 16px
  - Gap between cards: 12px
  - Section margin: 20px

### Components

#### Kanban Board
- 3 columns: Todo | In Progress | Done
- Drag-and-drop tasks between columns
- Each task card shows: title, subtask count, due date indicator

#### Calendar View
- Monthly calendar grid
- Tasks shown on their due dates
- Click date to see/add tasks

#### Task Card
- Title (required)
- Subtasks (checkbox list)
- Info (rich text - simple contenteditable div)
- Due date picker
- Status column
- Delete button

#### Add/Edit Modal
- Title input
- Subtasks section (add/remove)
- Info editor (simple toolbar: bold, italic, list)
- Due date input
- Save/Cancel buttons

## Functionality Specification

### Core Features
1. **Add Task**: Create new task with title
2. **Edit Task**: Modify task details
3. **Delete Task**: Remove task
4. **Subtasks**: Add/remove/toggle subtasks
5. **Rich Text Info**: Bold, italic, lists using contenteditable
6. **Due Dates**: Set/clear due date
7. **Kanban**: Drag tasks between Todo/In Progress/Done
8. **Calendar**: View tasks by date, add tasks from calendar

### User Interactions
- Click "+" button to add task
- Click task card to edit
- Drag card to change status (Kanban)
- Click calendar date to filter/view tasks

### Data Structure (tasks.json)
```json
[
  {
    "id": "uuid",
    "title": "Task title",
    "status": "todo|inprogress|done",
    "dueDate": "2026-03-15",
    "info": "<p>Rich text content</p>",
    "subtasks": [
      {"id": "uuid", "title": "Subtask 1", "done": false}
    ],
    "createdAt": "2026-03-01T10:00:00"
  }
]
```

## Acceptance Criteria
1. Tasks persist after page reload (saved to JSON file)
2. Kanban shows 3 columns with drag-drop working
3. Calendar shows current month with tasks on correct dates
4. Subtasks can be added, toggled, removed
5. Info field supports bold, italic, lists
6. Due dates can be set and display on cards
7. All CRUD operations work
8. No external dependencies (pure PHP/JS/CSS)
