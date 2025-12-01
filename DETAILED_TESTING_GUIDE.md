# Detailed Testing Guide - Complete Step-by-Step Instructions

## 📋 Table of Contents
1. [Pre-Testing Setup](#pre-testing-setup)
2. [Document Upload Testing](#document-upload-testing)
3. [PDF Chat Testing](#pdf-chat-testing)
4. [Document Insight Engine Testing](#document-insight-engine-testing)
5. [Decision Path Engine Testing](#decision-path-engine-testing)
6. [Scenario Foresight Engine Testing](#scenario-foresight-engine-testing)
7. [Role Alignment Engine Testing](#role-alignment-engine-testing)
8. [Leadership Alignment Brief Testing](#leadership-alignment-brief-testing)
9. [Integration Testing](#integration-testing)
10. [Troubleshooting](#troubleshooting)

---

## 🔧 Pre-Testing Setup

### Step 1: Environment Check
```bash
# Navigate to project directory
cd /Users/mac/Downloads/Projects/skilltricks

# Check PHP version (should be 7.4+ or 8.1+)
php -v

# Check Laravel is accessible
php artisan --version
```

### Step 2: Clear All Caches
```bash
# Clear application cache
php artisan cache:clear

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear

# Clear view cache
php artisan view:clear

# Clear compiled files
php artisan clear-compiled
```

**Expected Output:**
```
Application cache cleared!
Route cache cleared!
Configuration cache cleared!
Compiled views cleared!
```

### Step 3: Verify Environment Configuration
```bash
# Check .env file has required keys
grep -E "OPENAI_API_KEY|LLAMAPARSE_API_KEY" .env
```

**Expected:** Should show your API keys (even if empty, that's okay for some features)

### Step 4: Verify Routes
```bash
# Check new routes are registered
php artisan route:list | grep -E "users-new-chat|documents"
```

**Expected Routes:**
- `POST /dashboard/users-new-chat-ask`
- `POST /dashboard/users-new-chat-update-strategy`
- `POST /dashboard/users-new-chat-update-scenario`
- `POST /dashboard/users-new-chat-generate-alignment-brief`
- `POST /dashboard/users-new-chat-export-role-goals`
- `POST /dashboard/documents/upload`
- `GET /dashboard/documents`

---

## 📄 Document Upload Testing

### Test 1.1: PDF Upload (Baseline Test)

**Steps:**
1. Open browser and navigate to: `http://your-domain/dashboard/documents`
2. You should see:
   - "Upload Documents" card at the top
   - "My Documents" list below (may be empty)
   - File drop area with text: "Drop your document here or Browse"

**Expected UI Elements:**
```
┌─────────────────────────────────────┐
│  Upload Documents                   │
├─────────────────────────────────────┤
│  [📄 Drop area]                    │
│  Drop your document here or Browse │
│  * Allowed file types: PDF, DOC,   │
│    DOCX, XLSX, XLS, PPTX, PPT      │
│    (Max 10MB)                       │
│  [Upload Document Button]          │
└─────────────────────────────────────┘
```

3. **Upload a PDF file:**
   - Click "Browse" or drag & drop a PDF file
   - File should be less than 10MB
   - Click "Upload Document" button

**Expected Behavior:**
- File uploads (progress indicator may show)
- Success message appears: "Document uploaded successfully"
- Page refreshes or updates
- Document appears in "My Documents" table

**Expected Table Entry:**
```
┌─────────────────────────────────────────────────────────────┐
│ Name          │ Size    │ Uploaded │ Actions               │
├───────────────┼─────────┼──────────┼───────────────────────┤
│ example.pdf   │ 245 KB  │ Nov 30   │ [👁️] [📄] [🗑️]      │
│               │         │          │ PDF badge             │
└─────────────────────────────────────────────────────────────┘
```

4. **Verify Parsing:**
   - Click the blue info button (📄) next to the document
   - Should show parsed text in a modal
   - Text should be extracted from the PDF

**Success Criteria:**
- ✅ File uploads without errors
- ✅ Document appears in list
- ✅ Shows "PDF" badge
- ✅ File size is displayed
- ✅ Parsed text is available
- ✅ Parse status shows "completed"

---

### Test 1.2: DOCX Upload (New Feature)

**Steps:**
1. Prepare a Word document (.docx file)
   - Create a simple document with text
   - Save as .docx format
   - Keep under 10MB

2. **Upload the DOCX file:**
   - Navigate to documents page
   - Click "Browse"
   - Select your .docx file
   - Click "Upload Document"

**Expected Behavior:**
- File uploads successfully
- Success message: "Document uploaded successfully"
- Document appears in list with "DOC" badge (not "DOCX")

**Expected Table Entry:**
```
┌─────────────────────────────────────────────────────────────┐
│ Name              │ Size    │ Uploaded │ Actions           │
├───────────────────┼─────────┼──────────┼───────────────────┤
│ document.docx     │ 45 KB   │ Nov 30   │ [👁️] [📄] [🗑️]  │
│                   │         │          │ DOC badge         │
└─────────────────────────────────────────────────────────────┘
```

3. **Verify Parsing:**
   - Click the info button (📄)
   - Should show extracted text from the Word document
   - Text should match content from the document

**Detailed Verification:**
- Open the parsed text modal
- Check that:
  - ✅ Text is extracted correctly
  - ✅ No garbled characters
  - ✅ Formatting is preserved (basic)
  - ✅ All paragraphs are included

**Success Criteria:**
- ✅ DOCX file uploads successfully
- ✅ Shows "DOC" badge (normalized type)
- ✅ Parsing completes (check parse status)
- ✅ Text is extracted correctly
- ✅ No parsing errors in logs

**If Parsing Fails:**
- Check browser console for errors
- Check Laravel logs: `storage/logs/laravel.log`
- If LlamaParse is not configured, DOCX should still parse using local method

---

### Test 1.3: XLSX Upload (New Feature)

**Steps:**
1. Prepare an Excel file (.xlsx)
   - Create a spreadsheet with:
     - Multiple sheets (Sheet1, Sheet2)
     - Data in cells (text, numbers)
     - Headers in first row
   - Save as .xlsx format

2. **Upload the XLSX file:**
   - Navigate to documents page
   - Select your .xlsx file
   - Click "Upload Document"

**Expected Behavior:**
- File uploads successfully
- Document appears with "XLSX" badge
- Parsing may take slightly longer (processing multiple sheets)

**Expected Parsed Text Format:**
```
=== Sheet: Sheet1 ===

Header1 | Header2 | Header3
Value1  | Value2  | Value3
Value4  | Value5  | Value6

=== Sheet: Sheet2 ===

HeaderA | HeaderB
Data1   | Data2
```

3. **Verify Parsing:**
   - Click info button (📄)
   - Should show:
     - ✅ Sheet names (=== Sheet: Sheet1 ===)
     - ✅ All cell data
     - ✅ Data separated by " | "
     - ✅ All sheets included

**Success Criteria:**
- ✅ XLSX file uploads successfully
- ✅ Shows "XLSX" badge
- ✅ All sheets are parsed
- ✅ Cell data is extracted correctly
- ✅ Format is readable

---

### Test 1.4: PPTX Upload (New Feature)

**Steps:**
1. Prepare a PowerPoint file (.pptx)
   - Create a presentation with:
     - Multiple slides (at least 3)
     - Text on each slide
     - Titles and bullet points
   - Save as .pptx format

2. **Upload the PPTX file:**
   - Navigate to documents page
   - Select your .pptx file
   - Click "Upload Document"

**Expected Behavior:**
- File uploads successfully
- Document appears with "PPT" badge
- Parsing extracts text from slides

**Expected Parsed Text Format:**
```
=== Slide 1 ===

Title of Slide 1
Bullet point 1
Bullet point 2

=== Slide 2 ===

Title of Slide 2
Content text here

=== Slide 3 ===
...
```

3. **Verify Parsing:**
   - Click info button (📄)
   - Should show:
     - ✅ Slide numbers (=== Slide 1 ===)
     - ✅ Text from each slide
     - ✅ Titles and content

**Success Criteria:**
- ✅ PPTX file uploads successfully
- ✅ Shows "PPT" badge
- ✅ All slides are parsed
- ✅ Text is extracted from each slide
- ✅ Slide structure is preserved

**Note:** Old .ppt files may require LlamaParse API or conversion to .pptx

---

### Test 1.5: File Validation Tests

**Test Invalid File Type:**
1. Try uploading a .txt file
2. **Expected:** Validation error: "The document must be a file of type: pdf, doc, docx, xlsx, xls, pptx, ppt"

**Test File Size Limit:**
1. Try uploading a file larger than 10MB
2. **Expected:** Validation error: "The document may not be greater than 10240 kilobytes"

**Test Empty Upload:**
1. Click "Upload Document" without selecting a file
2. **Expected:** Validation error: "The document field is required"

---

## 💬 PDF Chat Testing

### Test 2.1: PDF Chat with PDF File

**Steps:**
1. Navigate to PDF Chat: `http://your-domain/dashboard/pdf-chat`
2. You should see:
   - Chat interface
   - File upload area
   - Text input for prompt

**Expected UI:**
```
┌─────────────────────────────────────┐
│  PDF Chat                          │
├─────────────────────────────────────┤
│  [Chat messages area]              │
│                                     │
│  ┌───────────────────────────────┐ │
│  │ Type your message...          │ │
│  │ [📎 Select Document] [Send]   │ │
│  └───────────────────────────────┘ │
└─────────────────────────────────────┘
```

3. **Upload a PDF:**
   - Click "Select Document" (or "Select PDF File" - label may vary)
   - Select a PDF file
   - Enter a question: "What are the main points in this document?"
   - Click "Send"

**Expected Behavior:**
- File uploads
- Processing indicator shows
- Response is generated based on PDF content
- Response references content from the PDF

**Success Criteria:**
- ✅ PDF uploads successfully
- ✅ Chat processes the PDF
- ✅ Response is relevant to PDF content
- ✅ No errors in console

---

### Test 2.2: PDF Chat with DOCX File (New)

**Steps:**
1. In PDF Chat, click "Select Document"
2. **Important:** The file input should now accept: `.pdf,.doc,.docx,.xlsx,.xls,.pptx,.ppt`
3. Select a Word document (.docx)
4. Enter a question about the document
5. Click "Send"

**Expected Behavior:**
- File uploads (may show as "Select Document" instead of "Select PDF")
- Document is parsed
- Chat response uses content from the Word document
- Response is relevant and accurate

**Verification:**
- Check browser console (F12) - no errors
- Response should mention content from your Word doc
- Processing should complete successfully

**Success Criteria:**
- ✅ DOCX file uploads in PDF Chat
- ✅ Document is parsed correctly
- ✅ Chat response uses document content
- ✅ No parsing errors

---

### Test 2.3: PDF Chat with XLSX File (New)

**Steps:**
1. Upload an Excel file in PDF Chat
2. Ask a question like: "What data is in Sheet 2?" or "Summarize the data in this spreadsheet"
3. Click "Send"

**Expected Behavior:**
- Excel file is parsed (all sheets)
- Chat response references data from the spreadsheet
- Response may mention specific sheets or data points

**Success Criteria:**
- ✅ XLSX file uploads successfully
- ✅ All sheets are parsed
- ✅ Chat response references spreadsheet data
- ✅ Data is accurately represented

---

### Test 2.4: PDF Chat with PPTX File (New)

**Steps:**
1. Upload a PowerPoint file in PDF Chat
2. Ask: "What are the key points in this presentation?"
3. Click "Send"

**Expected Behavior:**
- PPTX file is parsed (all slides)
- Chat response summarizes presentation content
- May reference specific slides or topics

**Success Criteria:**
- ✅ PPTX file uploads successfully
- ✅ All slides are parsed
- ✅ Chat response references presentation content
- ✅ Content is accurately summarized

---

## 🧠 Document Insight Engine Testing

### Test 3.1: Basic Document Insights

**Prerequisites:**
- Upload at least 2-3 documents (mix of PDF, DOC, XLSX)
- Documents should contain relevant business/company information

**Steps:**
1. Navigate to AI Chat: `http://your-domain/dashboard/users-new-chat`
2. Enter a strategic goal/question, for example:
   - "Increase revenue by 30% in the next quarter"
   - "Launch a new product line by Q2"
   - "Improve customer satisfaction scores"

3. Click "Send" or submit the question

**Expected Response Structure:**
```
🧩 Chat Acknowledgement
[AI acknowledges your goal]

📁 Document Insights
[This is the key section to verify]

📊 Goal Assessment Summary
[Assessment of the goal]

📈 Scoring
[Impact, Feasibility, Alignment scores]

🗺️ Strategy Map (Decision Paths)
[3-4 decision paths]

🔮 Scenario Simulations
[3 scenarios]

👥 Rephrased Goals by Role
[Role-specific goals]

📌 Complementary Goals
[Additional goals]

✅ Final Outcome Summary
[Summary]
```

**Detailed Verification of Document Insights Section:**

**Expected Format:**
```
📁 Document Insights

Based on [Document Name 1] (PDF), the following insights emerge:

• Insight 1: [Specific insight that references document]
  - Dependency: [Specific dependency mentioned]
  - Risk: [Specific risk identified]

• Insight 2: [Another insight]
  - Strategic Anchor: [Key strategic point]
  - Conflict: [Conflicting priority if any]

• Insight 3: [Third insight]
  - Reference: "As noted in [Document Name 2] (XLSX)..."
  - Misalignment: [Potential misalignment risk]

• Insight 4: [Fourth insight if applicable]
  - Reference: [Document reference]
```

**Success Criteria:**
- ✅ Section appears with 📁 emoji
- ✅ At least 3 insights are generated
- ✅ At least 1 insight explicitly mentions a document name
- ✅ Insights mention:
  - ✅ Dependencies (specific teams, resources, processes)
  - ✅ Conflicting priorities (if applicable)
  - ✅ Strategic anchors (key points to build on)
  - ✅ Misalignment risks (potential issues)
- ✅ Insights are specific, not generic
- ✅ Insights are relevant to the uploaded documents

**Example of Good Insight:**
```
✅ GOOD: "Based on Q3_Financial_Report.pdf, revenue growth 
         depends heavily on the Sales team's ability to close 
         enterprise deals, which requires coordination with 
         the Product team for feature demonstrations."
```

**Example of Bad Insight (Generic):**
```
❌ BAD: "Revenue growth is important for the company."
```

---

### Test 3.2: Document Context Integration

**Steps:**
1. Upload a document with specific company information:
   - Company financial report
   - Product roadmap
   - Team structure document
   - Strategic plan

2. Ask a goal that directly relates to the document:
   - If you uploaded a financial report: "Reduce operational costs by 15%"
   - If you uploaded a product roadmap: "Accelerate the launch of Product X"

3. Verify the AI uses document context:
   - Insights should reference specific data from documents
   - Recommendations should be based on document content
   - Numbers, names, or facts from documents should appear

**Success Criteria:**
- ✅ AI response uses information from uploaded documents
- ✅ Specific data points from documents are referenced
- ✅ Recommendations are grounded in document content
- ✅ No generic responses that ignore document context

---

## 🗺️ Decision Path Engine Testing

### Test 4.1: Decision Paths Generation

**Steps:**
1. After entering a goal and receiving response, locate "🗺️ Strategy Map (Decision Paths)" section

**Expected Format:**
```
🗺️ Strategy Map (Decision Paths)

- Path 1 Name: [Brief description]
  Rationale: [Why this path]
  Teams Impacted: [Team A, Team B, Team C]
  Trade-offs: [What's gained vs. lost]
  Risk Level: [Low/Med/High]

- Path 2 Name: [Brief description]
  Rationale: [Why this path]
  Teams Impacted: [Team D, Team E]
  Trade-offs: [What's gained vs. lost]
  Risk Level: [Low/Med/High]

- Path 3 Name: [Brief description]
  Rationale: [Why this path]
  Teams Impacted: [Team F, Team G, Team H]
  Trade-offs: [What's gained vs. lost]
  Risk Level: [Low/Med/High]

- Path 4 Name: [Optional - may have 3 or 4 paths]
  ...
```

**Detailed Verification:**

**Check Each Path Has:**
1. **Rationale:**
   - ✅ Explains why this path is viable
   - ✅ References user's situation or document insights
   - ✅ Not generic ("This is a good option")

2. **Teams Impacted:**
   - ✅ Lists specific teams/roles
   - ✅ Not vague ("Multiple teams")
   - ✅ Examples: "Sales Team, Marketing Team, Product Team"

3. **Trade-offs:**
   - ✅ Clearly states what's gained
   - ✅ Clearly states what's lost/compromised
   - ✅ Balanced view

4. **Risk Level:**
   - ✅ One of: Low, Med, or High
   - ✅ Consistent formatting

**Success Criteria:**
- ✅ Exactly 3 or 4 paths (not 2, not 5+)
- ✅ Each path has all 4 required elements
- ✅ Paths are customized to the goal (not template-style)
- ✅ Paths reference situation or document insights
- ✅ Risk levels are appropriate

**Example of Good Path:**
```
✅ GOOD:
- Aggressive Market Expansion: Focus on rapid geographic 
  expansion into 3 new markets simultaneously.
  Rationale: Based on Q3_Financial_Report.pdf showing strong 
  cash reserves and market research indicating high demand.
  Teams Impacted: Sales Team (hiring needed), Marketing Team 
  (campaign development), Operations Team (logistics scaling)
  Trade-offs: Fast growth potential but requires significant 
  upfront investment and may strain current operations
  Risk Level: High
```

**Example of Bad Path (Generic):**
```
❌ BAD:
- Option 1: Do something
  Rationale: This is a good option
  Teams Impacted: Various teams
  Trade-offs: Some benefits and some drawbacks
  Risk Level: Medium
```

---

### Test 4.2: Strategy Selection

**Steps:**
1. In the Strategy Map section, you should see radio buttons or clickable options for each path
2. Click on one of the decision paths to select it

**Expected Behavior:**
- Selected path is visually highlighted
- May show "Selected" badge
- Subsequent sections (scenarios, role goals) update based on selection
- Selection is saved (persists on page reload)

**Success Criteria:**
- ✅ Paths are selectable (radio buttons or clickable)
- ✅ Selected path is visually distinct
- ✅ Selection triggers update of subsequent sections
- ✅ Selection persists

---

## 🔮 Scenario Foresight Engine Testing

### Test 5.1: Scenario Generation

**Steps:**
1. After selecting a decision path, locate "🔮 Scenario Simulations" section

**Expected Format:**
```
🔮 Scenario Simulations

**Acceleration Scenario (Best Case):**
• Key Risks:
  - Risk 1: [Specific risk]
  - Risk 2: [Specific risk]
  - Risk 3: [Specific risk]

• Cross-team Dependencies:
  - Team A depends on Team B for [specific deliverable]
  - Team C needs input from Team D on [specific topic]

• Timeline Impact:
  - [How this affects the timeline - specific dates/milestones]

• Role Friction Points:
  - [Specific role] may conflict with [specific role] over [issue]
  - [Department] may resist changes in [area]

• Operational Consequences:
  - [Specific operational impact]
  - [Resource requirements]

**Expected Scenario (Most Likely):**
• Key Risks: [...]
• Cross-team Dependencies: [...]
• Timeline Impact: [...]
• Role Friction Points: [...]
• Operational Consequences: [...]

**Risk Scenario (Worst Case):**
• Key Risks: [...]
• Cross-team Dependencies: [...]
• Timeline Impact: [...]
• Role Friction Points: [...]
• Operational Consequences: [...]
```

**Detailed Verification:**

**Check Each Scenario Has All 5 Elements:**
1. **Key Risks:**
   - ✅ Bullet points (not long paragraphs)
   - ✅ Specific risks (not generic)
   - ✅ At least 2-3 risks per scenario

2. **Cross-team Dependencies:**
   - ✅ Specific teams/roles mentioned
   - ✅ Specific deliverables or inputs
   - ✅ Clear dependency relationships

3. **Timeline Impact:**
   - ✅ How scenario affects schedule
   - ✅ Specific milestones or dates if applicable
   - ✅ Not vague ("may delay project")

4. **Role Friction Points:**
   - ✅ Specific roles that may conflict
   - ✅ Specific issues causing friction
   - ✅ Realistic conflict scenarios

5. **Operational Consequences:**
   - ✅ Specific operational impacts
   - ✅ Resource requirements
   - ✅ Practical implications

**Success Criteria:**
- ✅ All 3 scenarios are generated
- ✅ Each scenario has all 5 required elements
- ✅ Format is clean with bullet points (not long paragraphs)
- ✅ Content is specific and actionable
- ✅ Scenarios differ meaningfully from each other

**Example of Good Scenario:**
```
✅ GOOD:
**Acceleration Scenario (Best Case):**
• Key Risks:
  - Sales team may struggle to scale hiring fast enough
  - Market saturation in target regions could limit growth
  - Supply chain may not keep pace with demand

• Cross-team Dependencies:
  - Sales Team depends on Marketing for lead generation 
    materials by Week 2
  - Operations needs Product Team's feature roadmap by 
    Week 4 for capacity planning

• Timeline Impact:
  - Could achieve 30% revenue growth 2 months ahead of 
    schedule (Month 8 instead of Month 10)
  - Requires all teams to start immediately

• Role Friction Points:
  - Sales Manager may conflict with Marketing Manager 
    over lead quality vs. quantity
  - Operations Director may resist rapid scaling due to 
    quality concerns

• Operational Consequences:
  - Requires hiring 15 new sales reps within 60 days
  - Need to increase warehouse capacity by 40%
  - IT infrastructure must scale to support 3x traffic
```

**Example of Bad Scenario (Generic):**
```
❌ BAD:
**Acceleration Scenario:**
• Key Risks: Some things might go wrong
• Cross-team Dependencies: Teams need to work together
• Timeline Impact: Timeline might change
• Role Friction Points: People might disagree
• Operational Consequences: Operations will be affected
```

---

### Test 5.2: Scenario Selection

**Steps:**
1. In the Scenario Simulations section, click on one of the scenarios
2. Verify selection updates subsequent sections

**Expected Behavior:**
- Selected scenario is highlighted
- "👥 Rephrased Goals by Role" section updates
- "📌 Complementary Goals" section updates
- "✅ Final Outcome Summary" section updates
- Updates reflect the selected scenario

**Success Criteria:**
- ✅ Scenarios are selectable
- ✅ Selection triggers section updates
- ✅ Updated content reflects selected scenario
- ✅ Selection persists

---

## 👥 Role Alignment Engine Testing

### Test 6.1: Role Goals Generation

**Steps:**
1. After scenario selection, locate "👥 Rephrased Goals by Role" section

**Expected Format:**
```
👥 Rephrased Goals by Role

1. Sales Manager
Goal: [Role-specific goal that references scenario and dependencies]

2. Marketing Director
Goal: [Role-specific goal]

3. Product Manager
Goal: [Role-specific goal]

4. Operations Lead
Goal: [Role-specific goal]

5. Finance Director
Goal: [Role-specific goal]

[5-10 roles total]
```

**Detailed Verification:**

**Check Each Role Goal:**
1. **References Scenario:**
   - ✅ Mentions selected scenario or its implications
   - ✅ Not generic goal

2. **References Dependencies:**
   - ✅ Mentions at least one dependency
   - ✅ Shows understanding of inter-team needs

3. **Uses Leadership Language:**
   - ✅ Not OKR format ("Increase X by Y%")
   - ✅ More strategic/directional language
   - ✅ Example: "Ensure Sales team has necessary resources to execute rapid expansion while maintaining quality standards"

4. **Unique Per Role:**
   - ✅ Each role has different goal (not template)
   - ✅ Goals reflect role's specific responsibilities
   - ✅ Goals are customized

**Success Criteria:**
- ✅ 5-10 roles are generated
- ✅ Each goal references the selected scenario
- ✅ Each goal references at least one dependency
- ✅ Language is leadership-alignment style (not OKR)
- ✅ Goals are unique and role-specific
- ✅ No template-style repetition

**Example of Good Role Goal:**
```
✅ GOOD:
1. Sales Manager
Goal: Lead the Sales team in executing the aggressive 
      market expansion strategy, coordinating closely with 
      Marketing for lead generation and Operations for 
      delivery capacity. Ensure new hires are onboarded 
      within 30 days to meet the accelerated timeline, 
      while maintaining current customer relationships 
      that depend on your team's attention.
```

**Example of Bad Role Goal (Template/OKR):**
```
❌ BAD:
1. Sales Manager
Goal: Increase sales by 30% in Q2.
```

---

### Test 6.2: Role Goals Export

**Steps:**
1. After role goals are generated, look for "Export Role Goals to Spreadsheet" button
2. Button should appear below the role goals section

**Expected Button:**
```
[📥 Export Role Goals to Spreadsheet]
```

3. **Click the Export Button:**
   - Button should show loading state (optional)
   - File download should start
   - File name: `role_goals_YYYY-MM-DD_HHMMSS.xlsx`

4. **Open the Excel File:**

**Expected Excel Format:**
```
┌─────────────────────────────────────────────────────────────┐
│  Role-Based Goals Export                                    │
├─────────────────────────────────────────────────────────────┤
│  Goal: [Your original goal/question]                       │
│  Selected Strategy: [Selected decision path]               │
│  Selected Scenario: [Selected scenario]                     │
├─────────────────────────────────────────────────────────────┤
│  # │ Role          │ Goal                    │ Notes      │
├───┼───────────────┼─────────────────────────┼────────────┤
│ 1 │ Sales Manager │ [Full goal text]        │            │
│ 2 │ Marketing Dir │ [Full goal text]        │            │
│ 3 │ Product Mgr   │ [Full goal text]        │            │
│ ...                                                         │
└─────────────────────────────────────────────────────────────┘
```

**Detailed Verification:**
- ✅ File downloads successfully
- ✅ File opens in Excel/LibreOffice
- ✅ Header includes Goal, Strategy, Scenario
- ✅ All roles are listed
- ✅ Goals are complete (not truncated)
- ✅ Formatting is clean and professional
- ✅ Columns are properly sized

**Success Criteria:**
- ✅ Export button appears after role goals section
- ✅ Button is clickable and responsive
- ✅ File downloads without errors
- ✅ Excel file is properly formatted
- ✅ All data is included correctly

**If Export Fails:**
- Check browser console for JavaScript errors
- Check Laravel logs: `storage/logs/laravel.log`
- Verify Maatwebsite Excel package is installed: `composer show maatwebsite/excel`

---

## 📋 Leadership Alignment Brief Testing

### Test 7.1: Brief Generation

**Steps:**
1. After "✅ Final Outcome Summary" section, look for "Generate Leadership Alignment Brief" button
2. Button should appear below the final outcome section

**Expected Button:**
```
[📄 Generate Leadership Alignment Brief]
```

3. **Click the Button:**
   - Loading indicator should appear
   - Brief generation may take 10-30 seconds
   - Brief should appear below the button

**Expected Brief Format:**
```
📋 LEADERSHIP ALIGNMENT BRIEF

**Decision Chosen:**
[Aggressive Market Expansion / Selected decision path name]

**Scenario Selected:**
[Acceleration Scenario / Selected scenario name]

**Top 3 Risks:**
1. [Specific risk with brief description]
2. [Specific risk with brief description]
3. [Specific risk with brief description]

**Top 3 Dependencies:**
1. [Dependency 1 - specific teams/roles/resources]
2. [Dependency 2 - specific teams/roles/resources]
3. [Dependency 3 - specific teams/roles/resources]

**Teams Impacted:**
[Sales Team, Marketing Team, Product Team, Operations Team, 
 Finance Team, etc.]

**Alignment Score:**
[Low/Med/High] - [Brief rationale explaining the score]

**Recommended Next Step for Leadership:**
[1-2 sentences with specific, actionable recommendation for 
 leadership team]
```

**Detailed Verification:**

**Check All 7 Required Elements:**
1. **Decision Chosen:**
   - ✅ Matches the selected decision path
   - ✅ Not generic

2. **Scenario Selected:**
   - ✅ Matches the selected scenario
   - ✅ Correct scenario name

3. **Top 3 Risks:**
   - ✅ Exactly 3 risks (not 2, not 4+)
   - ✅ Each risk has brief description
   - ✅ Risks are specific and relevant

4. **Top 3 Dependencies:**
   - ✅ Exactly 3 dependencies
   - ✅ Specific teams/roles/resources mentioned
   - ✅ Clear and actionable

5. **Teams Impacted:**
   - ✅ Lists all relevant teams
   - ✅ Specific team names/roles
   - ✅ Not vague ("multiple teams")

6. **Alignment Score:**
   - ✅ One of: Low, Med, or High
   - ✅ Includes brief rationale
   - ✅ Rationale explains why this score

7. **Recommended Next Step:**
   - ✅ 1-2 sentences
   - ✅ Specific and actionable
   - ✅ Clear direction for leadership
   - ✅ Not generic ("meet and discuss")

**Success Criteria:**
- ✅ Button appears after final outcome
- ✅ Brief generates successfully
- ✅ All 7 required elements are present
- ✅ Format is executive-ready and consulting-style
- ✅ No fluff - concise and actionable
- ✅ Content is specific, not generic

**Example of Good Brief:**
```
✅ GOOD:
📋 LEADERSHIP ALIGNMENT BRIEF

**Decision Chosen:**
Aggressive Market Expansion into 3 new geographic markets

**Scenario Selected:**
Acceleration Scenario (Best Case)

**Top 3 Risks:**
1. Sales team hiring may not scale fast enough, risking 
   missed revenue targets
2. Supply chain capacity constraints could limit ability 
   to meet increased demand
3. Market saturation in target regions may reduce expected 
   growth rates

**Top 3 Dependencies:**
1. Marketing Team must deliver lead generation materials 
   to Sales by Week 2
2. Operations requires Product Team's feature roadmap by 
   Week 4 for capacity planning
3. Finance Team needs to approve additional budget for 
   15 new hires within 30 days

**Teams Impacted:**
Sales Team (hiring 15 new reps), Marketing Team (campaign 
development), Product Team (roadmap delivery), Operations 
Team (capacity scaling), Finance Team (budget approval), 
IT Team (infrastructure scaling)

**Alignment Score:**
Medium - Strong strategic alignment but execution risks 
require careful coordination. Success depends on all teams 
meeting tight deadlines and maintaining quality standards.

**Recommended Next Step for Leadership:**
Schedule a cross-functional leadership meeting within 48 
hours to assign ownership for each dependency, establish 
weekly check-in cadence, and approve the additional budget 
required for rapid scaling.
```

**Example of Bad Brief (Generic):**
```
❌ BAD:
📋 LEADERSHIP ALIGNMENT BRIEF

**Decision Chosen:**
Some strategy

**Scenario Selected:**
A scenario

**Top 3 Risks:**
1. Things might go wrong
2. Some risks exist
3. Potential issues

**Top 3 Dependencies:**
1. Teams need to work together
2. Resources are needed
3. Coordination required

**Teams Impacted:**
Various teams

**Alignment Score:**
Medium - It's okay

**Recommended Next Step for Leadership:**
Meet and discuss the plan.
```

---

## 🔗 Integration Testing

### Test 8.1: Complete Workflow

**Full End-to-End Test:**

1. **Upload Multiple Documents:**
   - Upload a PDF (financial report)
   - Upload a DOCX (strategic plan)
   - Upload an XLSX (team data)
   - ✅ Verify all upload successfully

2. **Navigate to AI Chat:**
   - Go to AI Chat page
   - ✅ Verify documents are available for context

3. **Enter Strategic Goal:**
   - Enter: "Launch Product X in 3 new markets by Q2"
   - Click Send
   - ✅ Verify response generates

4. **Verify Document Insights:**
   - Check "📁 Document Insights" section
   - ✅ Verify insights reference your documents
   - ✅ Verify 3-5 insights with dependencies/risks

5. **Select Decision Path:**
   - Review "🗺️ Strategy Map" section
   - ✅ Verify 3-4 paths with all required elements
   - Click to select a path
   - ✅ Verify selection works

6. **Select Scenario:**
   - Review "🔮 Scenario Simulations"
   - ✅ Verify 3 scenarios with all elements
   - Click to select a scenario
   - ✅ Verify role goals update

7. **Export Role Goals:**
   - Review "👥 Rephrased Goals by Role"
   - ✅ Verify goals reference scenario and dependencies
   - Click "Export Role Goals"
   - ✅ Verify Excel file downloads
   - ✅ Verify file contains all data

8. **Generate Alignment Brief:**
   - Scroll to "✅ Final Outcome Summary"
   - Click "Generate Leadership Alignment Brief"
   - ✅ Verify brief generates
   - ✅ Verify all 7 elements present

**Success Criteria:**
- ✅ All steps complete without errors
- ✅ All features work together
- ✅ Data flows correctly between sections
- ✅ No crashes or JavaScript errors
- ✅ User experience is smooth

---

## 🐛 Troubleshooting

### Issue: Documents Not Parsing

**Symptoms:**
- Upload succeeds but parsing fails
- Parse status shows "failed"
- No parsed text available

**Solutions:**
1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. For DOC/PPT files: Ensure LlamaParse API key is configured, or convert to DOCX/PPTX
3. For XLSX: Verify PhpSpreadsheet is installed: `composer show phpoffice/phpspreadsheet`
4. Check file permissions: `chmod -R 755 storage/`

### Issue: Export Button Not Appearing

**Symptoms:**
- Role goals section shows but no export button
- Button doesn't appear after role goals

**Solutions:**
1. Check browser console (F12) for JavaScript errors
2. Verify JavaScript is enabled
3. Check that role goals section contains "👥" emoji
4. Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
5. Check view file: `resources/views/backend/pages/aiChat/users-new-chat.blade.php`

### Issue: Alignment Brief Not Generating

**Symptoms:**
- Button clicks but nothing happens
- Error message appears
- Brief doesn't generate

**Solutions:**
1. Check OpenAI API key in `.env`: `OPENAI_API_KEY=your-key`
2. Check browser console for errors
3. Check Laravel logs for API errors
4. Verify network connection
5. Check API quota/limits

### Issue: Routes Not Working

**Symptoms:**
- 404 errors on new routes
- Routes not found

**Solutions:**
1. Clear route cache: `php artisan route:clear`
2. Verify routes in `routes/backend.php`
3. Check route names match controller methods
4. Run: `php artisan route:list | grep users-new-chat`

### Issue: File Upload Validation Errors

**Symptoms:**
- "File type not allowed" errors
- Validation fails for valid files

**Solutions:**
1. Verify MIME types in validation: `mimes:pdf,doc,docx,xlsx,xls,pptx,ppt`
2. Check file extension matches MIME type
3. Verify file isn't corrupted
4. Check file size is under 10MB

---

## 📊 Test Results Template

```
TEST RESULTS LOG
================

Date: ___________
Tester: ___________
Environment: [ ] Development [ ] Staging [ ] Production

DOCUMENT UPLOAD TESTS
---------------------
PDF Upload:        [ ] Pass [ ] Fail [ ] Partial
DOCX Upload:       [ ] Pass [ ] Fail [ ] Partial
XLSX Upload:       [ ] Pass [ ] Fail [ ] Partial
PPTX Upload:       [ ] Pass [ ] Fail [ ] Partial
File Validation:   [ ] Pass [ ] Fail [ ] Partial

Notes: _________________________________________________

PDF CHAT TESTS
--------------
PDF Chat:          [ ] Pass [ ] Fail [ ] Partial
DOCX Chat:         [ ] Pass [ ] Fail [ ] Partial
XLSX Chat:         [ ] Pass [ ] Fail [ ] Partial
PPTX Chat:         [ ] Pass [ ] Fail [ ] Partial

Notes: _________________________________________________

DOCUMENT INSIGHT ENGINE
-----------------------
Insights Generated: [ ] Pass [ ] Fail [ ] Partial
Document References: [ ] Pass [ ] Fail [ ] Partial
Dependencies:       [ ] Pass [ ] Fail [ ] Partial
Risks Identified:    [ ] Pass [ ] Fail [ ] Partial

Notes: _________________________________________________

DECISION PATH ENGINE
--------------------
Paths Generated:    [ ] Pass [ ] Fail [ ] Partial
All Elements:       [ ] Pass [ ] Fail [ ] Partial
Path Selection:     [ ] Pass [ ] Fail [ ] Partial

Notes: _________________________________________________

SCENARIO FORESIGHT ENGINE
-------------------------
Scenarios Generated: [ ] Pass [ ] Fail [ ] Partial
All Elements:        [ ] Pass [ ] Fail [ ] Partial
Scenario Selection:  [ ] Pass [ ] Fail [ ] Partial

Notes: _________________________________________________

ROLE ALIGNMENT ENGINE
---------------------
Role Goals:         [ ] Pass [ ] Fail [ ] Partial
Scenario References: [ ] Pass [ ] Fail [ ] Partial
Dependency References: [ ] Pass [ ] Fail [ ] Partial
Export Function:    [ ] Pass [ ] Fail [ ] Partial

Notes: _________________________________________________

LEADERSHIP ALIGNMENT BRIEF
---------------------------
Brief Generation:   [ ] Pass [ ] Fail [ ] Partial
All 7 Elements:     [ ] Pass [ ] Fail [ ] Partial
Format Quality:     [ ] Pass [ ] Fail [ ] Partial

Notes: _________________________________________________

INTEGRATION TEST
----------------
Full Workflow:      [ ] Pass [ ] Fail [ ] Partial
Error Handling:     [ ] Pass [ ] Fail [ ] Partial
Performance:        [ ] Pass [ ] Fail [ ] Partial

Notes: _________________________________________________

OVERALL ASSESSMENT
------------------
Total Tests: _____
Passed: _____
Failed: _____
Partial: _____

Critical Issues: _______________________________________

Recommendations: _______________________________________

```

---

## ✅ Final Checklist

Before considering testing complete, verify:

- [ ] All document types upload and parse
- [ ] PDF Chat works with all file types
- [ ] Document insights reference specific documents
- [ ] Decision paths have all required elements
- [ ] Scenarios include all 5 elements per scenario
- [ ] Role goals are unique and reference scenario/dependencies
- [ ] Export functionality works correctly
- [ ] Leadership brief has all 7 required elements
- [ ] No critical errors in logs
- [ ] UI is responsive and user-friendly
- [ ] All buttons and interactions work
- [ ] Error messages are clear and helpful

---

**End of Detailed Testing Guide**

