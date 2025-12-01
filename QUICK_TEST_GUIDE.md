# Quick Test Guide

## 🚀 Quick Start Testing

### 1. Clear Caches First
```bash
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
```

### 2. Test Document Upload (5 minutes)

**Test PDF Upload:**
1. Go to: `/dashboard/documents` (or your documents route)
2. Upload a PDF file
3. ✅ Should see: "Document uploaded successfully"
4. ✅ Should see: Document in list with "PDF" badge

**Test DOC/DOCX Upload:**
1. Upload a Word document (.doc or .docx)
2. ✅ Should see: Document with "DOC" badge
3. ✅ Should parse successfully

**Test XLSX Upload:**
1. Upload an Excel file (.xlsx)
2. ✅ Should see: Document with "XLSX" badge
3. ✅ Should parse all sheets

**Test PPTX Upload:**
1. Upload a PowerPoint file (.pptx)
2. ✅ Should see: Document with "PPT" badge
3. ✅ Should parse slides

### 3. Test PDF Chat with New Formats (5 minutes)

1. Go to PDF Chat section
2. Try uploading:
   - PDF file ✅
   - DOC/DOCX file ✅
   - XLSX file ✅
   - PPTX file ✅
3. Enter a question about the document
4. ✅ Should process and respond correctly

### 4. Test Document Insight Engine (10 minutes)

1. Upload 2-3 documents (mix of PDF, DOC, XLSX)
2. Go to AI Chat
3. Enter a strategic goal like: "Increase revenue by 30% in Q2"
4. Look for "📁 Document Insights" section
5. ✅ Should see 3-5 insights
6. ✅ At least 1 insight should mention a document name
7. ✅ Should mention dependencies, conflicts, strategic anchors, risks

### 5. Test Decision Path Engine (5 minutes)

1. In the AI Chat response, find "🗺️ Strategy Map (Decision Paths)"
2. ✅ Should see 3-4 decision paths
3. Each path should show:
   - ✅ Rationale
   - ✅ Teams impacted
   - ✅ Trade-offs
   - ✅ Risk level (Low/Med/High)
4. Click on a strategy to select it
5. ✅ Should see it marked as "Selected"

### 6. Test Scenario Foresight Engine (5 minutes)

1. After selecting a strategy, find "🔮 Scenario Simulations"
2. ✅ Should see 3 scenarios:
   - Acceleration Scenario
   - Expected Scenario
   - Risk Scenario
3. Each scenario should include:
   - ✅ Key risks (bullet points)
   - ✅ Cross-team dependencies
   - ✅ Timeline impact
   - ✅ Role friction points
   - ✅ Operational consequences
4. Click on a scenario
5. ✅ Should update role goals section

### 7. Test Role Alignment Engine & Export (5 minutes)

1. After scenario selection, find "👥 Rephrased Goals by Role"
2. ✅ Should see 5-10 roles with goals
3. ✅ Each goal should reference scenario and dependencies
4. Look for "Export Role Goals to Spreadsheet" button
5. Click the button
6. ✅ Should download Excel file
7. Open the file
8. ✅ Should have Goal, Strategy, Scenario in header
9. ✅ Should list all roles with their goals

### 8. Test Leadership Alignment Brief (5 minutes)

1. Scroll to "✅ Final Outcome Summary" section
2. Look for "Generate Leadership Alignment Brief" button
3. Click the button
4. ✅ Should show loading indicator
5. ✅ Should generate and display brief
6. Brief should include:
   - ✅ Decision Chosen
   - ✅ Scenario Selected
   - ✅ Top 3 Risks
   - ✅ Top 3 Dependencies
   - ✅ Teams Impacted
   - ✅ Alignment Score (Low/Med/High)
   - ✅ Recommended Next Step

---

## ⚠️ Common Issues & Fixes

### Issue: "Class not found" errors
**Fix:** Run `composer dump-autoload`

### Issue: Routes not working
**Fix:** Run `php artisan route:clear && php artisan route:cache`

### Issue: Export button not appearing
**Fix:** Check browser console for JavaScript errors

### Issue: Parsing fails for DOC/PPT
**Fix:** Ensure LlamaParse API key is configured, or convert files to DOCX/PPTX

### Issue: OpenAI API errors
**Fix:** Check `.env` file has valid `OPENAI_API_KEY`

---

## ✅ Success Indicators

- [x] All file types upload successfully
- [x] Documents parse correctly
- [x] PDF Chat works with all formats
- [x] Document insights reference documents
- [x] Decision paths have all required elements
- [x] Scenarios include risks and dependencies
- [x] Role goals export works
- [x] Leadership brief generates correctly

---

## 📝 Test Results Log

```
Date: ___________
Tester: ___________

Document Upload: [ ] Pass [ ] Fail
PDF Chat: [ ] Pass [ ] Fail
Document Insights: [ ] Pass [ ] Fail
Decision Paths: [ ] Pass [ ] Fail
Scenarios: [ ] Pass [ ] Fail
Role Goals Export: [ ] Pass [ ] Fail
Leadership Brief: [ ] Pass [ ] Fail

Notes: ___________________
```

