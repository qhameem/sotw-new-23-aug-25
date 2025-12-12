# Todo List App - Context Documentation

## Overview
This document provides context about the Todo List application, its components, and important changes made during development.

## App Structure
- Main components located in `resources/js/components/`
- Constants located in `resources/js/constants/`
- Backend integration with Laravel framework
- Uses Vue.js for frontend functionality

## Components
- `TodoListApp.vue` - Main application component
- `TodoList.vue` - Core todo list functionality
- `TodoItem.vue` - Individual todo item component
- `AddTaskForm.vue` - Form for adding new tasks
- `PriorityFilter.vue` - Component for filtering by priority
- `ListDropdown.vue` - Dropdown for switching between lists
- Components in `resources/js/components/todo/` - Additional todo-specific components

## Constants
- `priorityColors.js` - Defines priority colors, names, numbers, and ordering

## Recent Changes

### Priority Color Display Fix (2025-11-17)
**Issue**: The "Normal" priority tag was showing as gray instead of the emerald-500 color.

**Files affected**:
- `resources/js/components/TodoItem.vue` - Lines 131 and 370-372

**Problem**: Two issues were causing incorrect color display:
1. The `priorityColors` variable was incorrectly mapped to `PRIORITY_NUMBERS` instead of `PRIORITY_COLORS`
2. CSS class had a typo: `.priority-tag.bg-emerald-50` instead of `.priority-tag.bg-emerald-500`

**Solution**:
1. Updated `priorityColors` mapping to use `PRIORITY_COLORS` constant
2. Fixed CSS class from `bg-emerald-50` to `bg-emerald-500`

**Before**:
```javascript
priorityColors: PRIORITY_NUMBERS, // Map of color name to priority number
```

**After**:
```javascript
priorityColors: PRIORITY_COLORS, // Map of color name to Tailwind color class
```

**Before**:
```css
.priority-tag.bg-emerald-50 {
   background-color: #10b981 !important;
}
```

**After**:
```css
.priority-tag.bg-emerald-500 {
   background-color: #10b981 !important;
}
```

### Dynamic Color Class Fix (2025-11-17)
**Issue**: The dynamic Tailwind classes were not using the correct color values from the constants file.

**Files affected**:
- `resources/js/components/TodoItem.vue` - Lines 59, 159-163, and 75

**Problem**:
1. The template was constructing class names dynamically with `:class="bg-${resolvedColor}-500"` which would always append `-500` regardless of the actual color class defined in constants
2. The color picker was also using the same problematic approach
3. This caused incorrect color display (e.g., amber would try to use `bg-amber-500` instead of the correct `bg-amber-600`)

**Solution**:
1. Added a new computed property `resolvedColorClass` that returns the actual Tailwind class from `PRIORITY_COLORS` based on the resolved color
2. Updated the template to use `:class="bg-${resolvedColorClass}"` instead of constructing the class name
3. Updated the color picker to use the correct color class from the `priorityColors` object

**Before**:
```html
:class="`bg-${resolvedColor}-500`"
```

**After**:
```html
:class="`bg-${resolvedColorClass}`"
```

With the computed property:
```javascript
resolvedColorClass() {
  return this.priorityColors[this.resolvedColor] || 'emerald-500';
}
```

### Import Path Fix (2025-11-17)
**Issue**: The application was failing to resolve import for `./constants/priorityColors` in multiple components.

**Files affected**:
- `resources/js/components/TodoList.vue` - Line 72
- `resources/js/components/TodoListApp.vue` - Line 270

**Problem**: Both files were using `'./constants/priorityColors'` but needed to use `'../constants/priorityColors'` since:
- Components are located in `resources/js/components/`
- Constants are located in `resources/js/constants/`
- Need to go up one directory level to access the constants folder

**Solution**: Updated import paths to correctly reference the priorityColors constants file.

**Before**:
```javascript
import { PRIORITY_ORDER } from './constants/priorityColors'; // Incorrect
```

**After**:
```javascript
import { PRIORITY_ORDER } from '../constants/priorityColors'; // Correct
```

### Priority Tag Styling Enhancement (2025-11-17)
**Issue**: The priority tags inside todo list items needed improved styling with white text and borders matching the todo list items.

**Files affected**:
- `resources/js/constants/priorityColors.js` - Updated color definitions
- `resources/js/components/TodoItem.vue` - Added border and text color styling
- `resources/js/components/TodoListApp.vue` - Updated import and text color
- `resources/js/components/PriorityFilter.vue` - Updated import and data
- `resources/js/components/todo/TaskItem.vue` - Updated import and color mapping
- `resources/js/components/todo/PriorityFilter.vue` - Updated import

**Problem**:
1. Priority colors were defined as light variants (`rose-50`, `amber-50`, `emerald-50`) which appeared grayish
2. Text inside priority tags was not white, causing readability issues
3. Priority tags lacked borders to match the todo list items style

**Solution**:
1. Updated `PRIORITY_COLORS` in constants to use proper color classes (`rose-500`, `amber-600`, `emerald-500`)
2. Added CSS rules to ensure text inside priority tags is white
3. Added border styling to priority tags in TodoItem.vue to match todo list items
4. Updated all components to properly import and use the new color constants
5. Ensured consistent color handling across all components

**Before**:
```javascript
// In priorityColors.js
'rose': 'rose-50', // Light/grayish color
'amber': 'amber-50', // Light/grayish color
'emerald': 'emerald-50' // Light/grayish color
```

**After**:
```javascript
// In priorityColors.js
'rose': 'rose-500', // Proper rose color
'amber': 'amber-600', // Proper amber color
'emerald': 'emerald-500' // Proper emerald color
```

**CSS additions for priority tags**:
```css
.priority-tag {
  color: white !important;
  border: var(--border-width, 1.5px) solid var(--border-color, #9ca3af);
}
```

## Priority System
- Uses color-coded priorities: rose (urgent), amber (high), emerald (normal)
- Priorities determine sorting order in the list
- Priority colors are displayed as background colors on todo items

## Backend Integration
- API endpoints for creating, updating, and deleting todo lists and items
- CSRF token integration for security
- Export functionality to Excel format

## Styling
- Uses Tailwind CSS for styling
- Responsive design for different screen sizes
- Color-coded priority indicators