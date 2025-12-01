# Testing Checklist for Document Insight Engine & Enhanced Features

## Pre-Testing Setup
- [ ] Ensure Laravel application is running
- [ ] Verify OpenAI API key is configured in `.env`
- [ ] Verify LlamaParse API key is configured (optional, for better parsing)
- [ ] Clear application cache: `php artisan cache:clear`
- [ ] Clear route cache: `php artisan route:clear`
- [ ] Clear config cache: `php artisan config:clear`

---

## 1. Document Upload & Parsing Tests

### Test 1.1: PDF Upload (Existing Functionality)
- [ ] Navigate to Documents section
- [ ] Upload a PDF file (max 10MB)
- [ ] Verify file uploads successfully
- [ ] Verify document appears in "My Documents" list
- [ ] Verify document type shows as "PDF"
- [ ] Click "Parse Document" if needed
- [ ] Verify parsed text is extracted and displayed

### Test 1.2: DOC/DOCX Upload (New)
- [ ] Upload a Word document (.doc or .docx)
- [ ] Verify file uploads successfully
- [ ] Verify document type shows as "DOC"
- [ ] Verify parsing completes (may use LlamaParse or local parser)
- [ ] Verify parsed text is extracted
- [ ] Check that document name and type are correctly stored

### Test 1.3: XLSX/XLS Upload (New)
- [ ] Upload an Excel file (.xlsx or .xls)
- [ ] Verify file uploads successfully
- [ ] Verify document type shows as "XLSX"
- [ ] Verify parsing extracts text from all sheets
- [ ] Verify parsed text includes sheet names and cell data

### Test 1.4: PPT/PPTX Upload (New)
- [ ] Upload a PowerPoint file (.ppt or .pptx)
- [ ] Verify file uploads successfully
- [ ] Verify document type shows as "PPT"
- [ ] Verify parsing extracts text from slides
- [ ] Verify parsed text includes slide numbers

### Test 1.5: File Validation
- [ ] Try uploading an unsupported file type (e.g., .txt, .jpg)
- [ ] Verify validation error is shown
- [ ] Try uploading a file larger than 10MB
- [ ] Verify size validation error is shown

---

## 2. PDF Chat with New File Types

### Test 2.1: PDF Chat with PDF (Existing)
- [ ] Navigate to PDF Chat section
- [ ] Upload a PDF file
- [ ] Enter a prompt/question
- [ ] Verify chat processes the PDF correctly
- [ ] Verify response is generated

### Test 2.2: PDF Chat with DOC/DOCX (New)
- [ ] Upload a Word document in PDF Chat
- [ ] Enter a prompt/question
- [ ] Verify chat processes the document correctly
- [ ] Verify response references document content

### Test 2.3: PDF Chat with XLSX (New)
- [ ] Upload an Excel file in PDF Chat
- [ ] Enter a prompt/question about the data
- [ ] Verify chat processes the spreadsheet correctly
- [ ] Verify response includes relevant data from sheets

### Test 2.4: PDF Chat with PPTX (New)
- [ ] Upload a PowerPoint file in PDF Chat
- [ ] Enter a prompt/question
- [ ] Verify chat processes the presentation correctly
- [ ] Verify response includes content from slides

---

## 3. Document Insight Engine Tests

### Test 3.1: Document Insights Generation
- [ ] Navigate to AI Chat
- [ ] Upload at least 2-3 documents (different types: PDF, DOC, XLSX)
- [ ] Enter a strategic goal/question
- [ ] Verify response includes "📁 Document Insights" section
- [ ] Verify at least 3-5 insights are generated
- [ ] Verify at least 1 insight explicitly references a document name
- [ ] Verify insights mention:
  - [ ] Dependencies
  - [ ] Conflicting priorities
  - [ ] Strategic anchors
  - [ ] Misalignment risks

### Test 3.2: Document Context Integration
- [ ] Upload documents with specific company information
- [ ] Ask a goal that relates to the document content
- [ ] Verify AI response uses document context
- [ ] Verify insights are relevant to uploaded documents

---

## 4. Decision Path Engine Tests

### Test 4.1: Decision Paths Generation
- [ ] Enter a strategic goal in AI Chat
- [ ] Verify "🗺️ Strategy Map (Decision Paths)" section appears
- [ ] Verify exactly 3-4 decision paths are generated
- [ ] For each path, verify it includes:
  - [ ] Rationale (why this path)
  - [ ] Teams impacted (specific teams/roles listed)
  - [ ] Trade-offs (what's gained vs. lost)
  - [ ] Risk level (Low/Med/High)

### Test 4.2: Strategy Selection
- [ ] Select a decision path/strategy
- [ ] Verify selection is saved
- [ ] Verify subsequent sections update based on selection
- [ ] Verify selected strategy is highlighted

---

## 5. Scenario Foresight Engine Tests

### Test 5.1: Scenario Generation
- [ ] After selecting a decision path, verify "🔮 Scenario Simulations" section
- [ ] Verify 3 scenarios are generated:
  - [ ] Acceleration Scenario (best case)
  - [ ] Expected Scenario (most likely)
  - [ ] Risk Scenario (worst case)

### Test 5.2: Scenario Details
- [ ] For each scenario, verify it includes:
  - [ ] Key risks (as bullet points, not long paragraphs)
  - [ ] Cross-team dependencies (specific teams/roles)
  - [ ] Timeline impact
  - [ ] Role friction points
  - [ ] Operational consequences

### Test 5.3: Scenario Selection
- [ ] Select a scenario
- [ ] Verify selection updates subsequent sections
- [ ] Verify role goals are regenerated based on selected scenario

---

## 6. Role Alignment Engine Tests

### Test 6.1: Role Goals Generation
- [ ] After scenario selection, verify "👥 Rephrased Goals by Role" section
- [ ] Verify 5-10 roles are generated
- [ ] Verify each role goal:
  - [ ] References the selected scenario
  - [ ] References at least one dependency
  - [ ] Uses leadership-alignment language (not OKR phrasing)
  - [ ] Is unique per role (not template-style)

### Test 6.2: Role Goals Export
- [ ] Verify "Export Role Goals to Spreadsheet" button appears after role goals section
- [ ] Click the export button
- [ ] Verify Excel file downloads
- [ ] Open the Excel file and verify:
  - [ ] Goal, Strategy, and Scenario are included in header
  - [ ] All roles are listed with their goals
  - [ ] Formatting is clean and professional
  - [ ] File name includes timestamp

---

## 7. Leadership Alignment Brief Tests

### Test 7.1: Brief Generation
- [ ] After final outcome section, verify "Generate Leadership Alignment Brief" button appears
- [ ] Click the button
- [ ] Verify loading indicator appears
- [ ] Verify brief is generated and displayed

### Test 7.2: Brief Content
- [ ] Verify brief includes all required elements:
  - [ ] Decision Chosen
  - [ ] Scenario Selected
  - [ ] Top 3 Risks
  - [ ] Top 3 Dependencies
  - [ ] Teams Impacted
  - [ ] Alignment Score (Low/Med/High)
  - [ ] Recommended Next Step for Leadership
- [ ] Verify format is executive-ready and consulting-style
- [ ] Verify no fluff, clean and structured

---

## 8. Integration Tests

### Test 8.1: Full Workflow
1. [ ] Upload multiple documents (PDF, DOC, XLSX)
2. [ ] Enter a strategic goal
3. [ ] Verify all sections generate correctly:
   - [ ] Document Insights
   - [ ] Goal Assessment
   - [ ] Scoring
   - [ ] Decision Paths
   - [ ] Scenarios
   - [ ] Role Goals
   - [ ] Complementary Goals
   - [ ] Final Outcome
4. [ ] Select a decision path
5. [ ] Select a scenario
6. [ ] Export role goals
7. [ ] Generate leadership alignment brief

### Test 8.2: Error Handling
- [ ] Test with no documents uploaded
- [ ] Test with invalid API key
- [ ] Test with network timeout
- [ ] Verify graceful error messages
- [ ] Verify application doesn't crash

---

## 9. UI/UX Tests

### Test 9.1: Visual Elements
- [ ] Verify file type badges display correctly (PDF, DOC, XLSX, PPT)
- [ ] Verify file type icons/colors are appropriate
- [ ] Verify export button styling
- [ ] Verify alignment brief button styling
- [ ] Verify all buttons are responsive

### Test 9.2: User Experience
- [ ] Verify loading states are clear
- [ ] Verify success messages appear
- [ ] Verify error messages are user-friendly
- [ ] Verify copy buttons work
- [ ] Verify navigation between sections is smooth

---

## 10. Performance Tests

### Test 10.1: Large Files
- [ ] Upload a large PDF (close to 10MB)
- [ ] Verify parsing completes in reasonable time
- [ ] Upload a large Excel file with multiple sheets
- [ ] Verify all sheets are parsed

### Test 10.2: Multiple Documents
- [ ] Upload 5+ documents
- [ ] Verify all are used in context
- [ ] Verify response generation time is acceptable

---

## Known Issues to Watch For

1. **LlamaParse API**: If not configured, DOC/PPT parsing may fall back to local methods
2. **Old DOC files**: May require LlamaParse API or conversion to DOCX
3. **Old PPT files**: May require LlamaParse API or conversion to PPTX
4. **Large Excel files**: May take longer to parse
5. **OpenAI API**: Rate limits may affect response generation

---

## Test Results Template

```
Date: ___________
Tester: ___________

Feature: ________________
Status: [ ] Pass [ ] Fail [ ] Partial
Notes: ___________________
```

---

## Quick Test Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

# Check routes
php artisan route:list | grep users-new-chat

# Check for syntax errors
php artisan tinker
# Then try: new App\Exports\RoleGoalsExport([], '', '', '');
```

---

## Success Criteria

✅ All document types (PDF, DOC, DOCX, XLSX, XLS, PPTX, PPT) upload and parse successfully
✅ Document insights reference specific documents
✅ Decision paths include all required elements
✅ Scenarios include risks and dependencies
✅ Role goals are unique and reference scenarios
✅ Export functionality works correctly
✅ Leadership alignment brief generates with all required elements
✅ No critical errors or crashes
✅ UI is responsive and user-friendly

