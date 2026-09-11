# UI standardization review — 2026-09-11

This pass replaces the earlier completion claims with a source audit and fresh verification. Square corners remain the product standard. Shared styles apply across tenant, auth, platform, and marketing screens; browser checks cover representative shared controls, not every authenticated workflow.

## 1. Shared components

- [x] `Table.vue`: removed conflicting 28px action sizing, duplicate toolbar/search declarations, and fixed input heights that could clip labels or wrapped selections. Small icon actions are 32px on desktop and at least 44px with a coarse pointer.
- [x] `Form.vue`, `EmployeeStepperForm.vue`, document dialogs, and `Permission.vue`: labeled icon actions; filled primary submissions and text dismissal actions.
- [x] `OrgChartTree.vue` and `RIchTextEditor.vue`: explicit accessible names on icon controls. Existing calendar, org-chart node, and app-dialog button variants retained.
- [x] `ModuleHeader.vue`: action buttons wrap on narrow screens without forced widths or heights.

## 2. Auth and onboarding

- [x] Existing reset, invitation, and trial button variants audited; shared control styles applied.
- [x] Tenant login, SSO continuation, and platform login primary buttons now explicitly use `variant="flat"`.

## 3. Tenant modules

- [x] Audited the original module list and Profile, Attendance Management, and Workforce Calendar for missing button variants and icon labels.
- [x] Fixed missing primary variants in billing, profile/MFA, document upload, and holiday import flows.
- [x] Added accessible names to unlabeled dialog, attachment, copy, and action-item controls. Action-item completion exposes its pressed state.
- [x] Preserved tonal secondary actions and text dismissal actions. Seven toggle buttons intentionally inherit their variant from their button groups.

## 4. Platform Console

- [x] Filled primary actions in login, onboarding, and health-threshold settings.
- [x] Labeled organization navigation, health refresh, and billing identifier copy controls.

## 5. Marketing

- [x] Corrected the remaining navigation “Start free” button to the filled primary variant.
- [x] Button casing and typography are enforced centrally; repeating `class="text-none"` is not required for the visual standard.

## 6. Inputs and interaction states

- [x] Extracted control styling from `App.vue` into `src/styles/controls.css`.
- [x] Preserved outlined/comfortable global field defaults in `src/plugins/vuetify.ts`; added light/dark `surface-field` colors.
- [x] Solid field surfaces, subtle resting borders, hover emphasis, and theme-colored 3px focus rings.
- [x] Validation labels/icons retain Vuetify error colors; focused errors use an error-colored ring.
- [x] Floating label sizing, selection-chip wrapping, and textarea auto-growth use Vuetify's layout rather than blanket height/font overrides.
- [x] Readonly and disabled surfaces are subdued; icon colors inherit their theme without overriding nested button contrast.
- [x] Date/time/month/week controls use the app's explicit color scheme and right-aligned native indicators. **No inversion filter**: dark native icons are already light.
- [x] Visible keyboard focus and reduced-motion support; mobile icon targets are at least 44px.

## 7. Verification

- [x] Production build: `npm run build`, 656 modules, TypeScript passed.
- [x] Frontend tests: `node --test tests/*.test.cjs`, 15 passed.
- [x] Backend tests: `php artisan test`, 187 passed, 1,683 assertions.
- [x] Tenancy audit: 69 tables passed.
- [x] Authorization and sensitive-field encryption audits passed.
- [x] Chrome desktop previews in light/dark themes: native icon scheme/filter, text/icon/error colors, 32px row actions, and normal/error focus rings verified.
- [x] Chrome interaction checks: clearing a field, opening a select, visible keyboard focus, and textarea growth from 66px to 150px passed.
- [x] Chrome 390px mobile viewport: no page overflow; coarse-pointer row actions measured 44px.

Remaining verification: Safari/Firefox and complete authenticated workflows on each screen. This UI pass does not advance SaaS launch or industry-readiness gates. Next step: review the refreshed local application with real employee and payroll data.
