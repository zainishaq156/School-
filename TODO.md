# Promotion Code Adjustment Tasks

## Issues Identified
- Code duplication between dashboard.php and promotion_api.php
- Inconsistent class hierarchies across files
- Mixed promotion/graduation logic
- Promoted students not appearing in next class for teachers

## Tasks to Complete
- [ ] Standardize class hierarchy (PG → Nursery → Prep → 1-8 → Graduated)
- [ ] Consolidate all promotion logic in promotion_api.php
- [ ] Update dashboard.php to use promotion_api.php instead of local functions
- [ ] Remove duplicate promotion functions from dashboard.php
- [ ] Ensure students.php properly integrates with promotion API
- [ ] Verify promoted students appear in correct class for teachers
- [ ] Test promotion functionality end-to-end

## Recently Completed
- [x] Add teacher notifications for student promotions
- [x] Create promotion notices system in promotion_api.php
- [x] Display promotion alerts on teacher dashboard
