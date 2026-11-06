# IJTS design guide (use this to build the screens in Figma)

**Product:** Internal Job Tracking System for a company with five departments (ICT, Accounts, Marketing, Engineering, Human Resources). HR publishes every vacancy.
**Users:** department heads, HR officers, HR manager (admin), employees.
**Core job of the interface:** show, at a glance, where every vacancy and application stands: request, application, interview stage, hiring decision.
**One memorable element:** the **progress tracker**, a row of connected dots (Received, Shortlisted, Interview, Decision). It appears on the login screen, dashboard, application page and as a mini version in tables. Everything else stays quiet.

## 1. Colour styles (create as Figma color styles)
| Name | Hex | Use |
|---|---|---|
| Ink | #0D3836 | Sidebar, selected tabs, login panel |
| Ink soft | #154B47 | Sidebar hover and active background |
| Primary | #0F766E | Buttons, links, completed tracker steps |
| Primary dark | #0B5B55 | Button hover, stat numbers |
| Marigold (accent) | #F2A516 | Current tracker step, active nav marker, focus ring |
| Canvas | #F2F5F4 | Page background |
| Surface | #FFFFFF | Panels, table, forms |
| Text | #16201F | Body text |
| Muted | #586966 | Secondary text, labels |
| Line | #DCE4E1 | Borders and dividers |
| Success | #1E7F4F | Selected, Published |
| Warning | #8A5200 on #FCEFD2 | Pending, Interview, On hold |
| Info | #1F4A87 on #DCE8F7 | Approved, Shortlisted |
| Danger | #B3363A | Rejected, destructive buttons |

Status badges use a tinted background with dark text of the same hue (see the style guide page).

## 2. Typography (Google Fonts, free)
- **Headings:** Bricolage Grotesque, 600 and 700.
- **Body and tables:** Source Sans 3, 400 and 600.
- Scale: Page title 24 / Panel title 18 / Body 16 / Table 15 / Small 14 / Stat number 34 (Bricolage 700).
- Sentence case everywhere. No all-caps labels. Buttons say what they do ("Send application", "Save decision").

## 3. Layout and spacing
- 8 px spacing grid. Panel padding 20 x 22. Gap between panels 22.
- Radius: 8 px panels, 6 px buttons and inputs, pill badges.
- Desktop frame **1440 x 900**: sidebar 260 px (Ink), content max width 1180 px, top bar 64 px.
- Mobile frame **390 x 844**: sidebar becomes a slide-in menu, grids become one column, tables scroll sideways.
- Panels are flat: 1 px Line border, no shadow. The stats strip is one bar split by dividers, not separate cards.

## 4. Components to build (Figma components with variants)
1. **Sidebar** (brand mark = three linked dots, nav items with 20 px line icons, active item has a Marigold left bar, user block with Sign out).
2. **Top bar** (page title, notification bell with red count).
3. **Stats strip** (3 to 4 segments: big number + label).
4. **Panel** (title, optional sub-text, optional action button).
5. **Table** (muted header row, 12 px cell padding, row hover tint).
6. **Badge** (variants: Pending, Approved, Published, Returned, Rejected, Closed, Filled, Received, Shortlisted, Interview, Selected, On hold, Passed, Failed).
7. **Tracker** (large with labels, mini without labels; states: done, current, final-selected, final-rejected, final-on-hold).
8. **Buttons** (primary, ghost, danger; small size).
9. **Form fields** (text, select, textarea, date, file; label above, hint below, error alert on top).
10. **Alerts** (success, error, info) and **tabs** (pill tabs for request status).

## 5. Screens to design (frames)
1. Login (split: Ink panel with tagline and tracker, white form).
2. Dashboard: HR view (stats strip, requests to review, latest applications, department table).
3. Dashboard: Department head view. 4. Dashboard: Employee view (trackers).
5. Open vacancies (search + department filter + table).
6. Vacancy details (meta row, description, qualifications, apply form / applications table).
7. New vacancy request form (and resubmit state with HR comment).
8. Department requests table (status, selected employee, HR comment).
9. Review requests (HR) with status tabs and Approve / Return / Reject actions.
10. Applications list (HR filters + mini trackers).
11. **Application detail (HR):** large tracker, interview stages table, "Record an interview stage" form, "Hiring decision" form.
12. Hiring records ("Who got which job") with CSV export.
13. Reports (bars by status, department summary).
14. Users (admin) and Audit log. 15. Notifications.

## 6. Accessibility rules
Text contrast 4.5:1 or better, visible 3 px Marigold focus ring, status is always shown with text (never colour alone), motion limited to the sidebar slide on mobile.
