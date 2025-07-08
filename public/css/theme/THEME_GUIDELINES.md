# Stock360 Theme Guidelines

## Core Principles

1. **Single Source of Truth**: All colors and styling variables MUST be defined ONLY in `_variables.css`. No color definitions anywhere else.
2. **No Inline or Internal CSS**: Never use inline styles (`style` attributes) or internal CSS (`<style>` tags).
3. **Component-Based Styling**: Create reusable CSS classes instead of one-off styles.
4. **Color References**: Always reference colors via CSS variables, never use direct color values.
5. **Bootstrap First**: Use Bootstrap's utility classes instead of writing custom CSS whenever possible.

## Implementation Rules

### DO:
- Use variables for all theme-related properties (`var(--primary-500)`, never `#3b82f6`)
- Create new component classes in the appropriate theme file
- Follow the established naming conventions
- Use utility classes for one-off styling needs
- Define ALL color variables in `_variables.css` only
- Use Bootstrap utility classes before creating custom CSS

### DON'T:
- Add inline styles (`style="color: blue;"`)
- Add internal stylesheets (`<style>` tags)
- Hardcode color values, shadows, or other theme properties
- Create page-specific CSS files
- Define colors or redefine color variables outside of `_variables.css`
- Use CSS preprocessor color functions unless absolutely necessary
- Write custom CSS for functionality that Bootstrap already provides

## Bootstrap Utilities

When styling components, follow this order:
1. Use Bootstrap utility classes first
2. Combine multiple utilities to achieve complex layouts
3. Create custom CSS only when Bootstrap utilities are insufficient

Common Bootstrap utilities to use instead of custom CSS:

| Instead of custom CSS for... | Use Bootstrap class            |
|-----------------------------|--------------------------------|
| Layout and positioning      | `d-flex`, `d-grid`, `position-absolute` |
| Margin and padding          | `m-2`, `p-3`, `my-4`, `px-lg-5` |
| Text alignment and style    | `text-center`, `fw-bold`, `fs-4` |
| Color                       | `text-primary`, `bg-success`   |
| Borders and shadows         | `border`, `rounded-3`, `shadow-sm` |
| Responsiveness              | `d-none d-md-block`, `flex-md-row` |

Example:
```html
<!-- Good: Using Bootstrap utilities -->
<div class="d-flex align-items-center p-3 mb-4 bg-light rounded-3 shadow-sm">
  <div class="flex-shrink-0">
    <img class="rounded-circle" src="avatar.jpg" width="50" height="50">
  </div>
  <div class="ms-3">
    <h5 class="mb-1 fw-bold">User Name</h5>
    <p class="mb-0 text-muted">User Role</p>
  </div>
</div>

<!-- Avoid: Custom CSS class for the same design -->
<div class="user-card">
  <div class="user-avatar">
    <img src="avatar.jpg">
  </div>
  <div class="user-info">
    <h5 class="user-name">User Name</h5>
    <p class="user-role">User Role</p>
  </div>
</div>
```

## Color System

Our theme uses a comprehensive color system with well-named variables:

- **Primary Colors**: `--primary-50` through `--primary-950`
- **Status Colors**: `--success`, `--danger`, `--warning`, `--info`
- **UI Colors**: `--text-color`, `--text-muted`, `--border-color`, etc.

Always use these variables instead of direct color values. If you need a new color:

1. Add it to `_variables.css` with an appropriate name
2. Document its purpose in a comment
3. Use it via the variable

## Directory Structure

```
resources/css/theme/
├── _variables.css      # ALL color variables - ONLY place where colors are defined
├── _layout.css         # Layout components - sidebar, navbar, etc.
├── _components.css     # UI components - buttons, cards, etc.
├── _utilities.css      # Utility classes - margins, paddings, etc.
└── theme.css           # Main entry point that imports all parts
```

## Code Review Checklist

- [ ] No inline styles or `<style>` tags
- [ ] No hardcoded color values, all using CSS variables
- [ ] No color variable definitions outside of `_variables.css`
- [ ] All new components follow established naming conventions
- [ ] No unnecessary CSS duplication
- [ ] Bootstrap utilities are used where appropriate
- [ ] Custom CSS is only used when Bootstrap utilities are insufficient

## Migration Path

1. Identify all instances of inline/internal CSS
2. Extract styles to appropriate theme files
3. Replace hardcoded values with variables
4. Move any color definitions to `_variables.css`
5. Replace custom CSS with Bootstrap utilities where possible
6. Update components to use new classes 