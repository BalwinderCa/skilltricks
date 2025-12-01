# Testing Quick Reference Card

## 🚀 Quick Start (5 Minutes)

```bash
# 1. Clear caches
php artisan cache:clear && php artisan route:clear && php artisan config:clear

# 2. Verify implementation
php verify_implementation.php

# 3. Start testing!
```

---

## 📋 Test Checklist (One-Page)

### Document Upload ✅
- [ ] PDF uploads and parses
- [ ] DOCX uploads and parses  
- [ ] XLSX uploads and parses
- [ ] PPTX uploads and parses
- [ ] File validation works (size, type)

### PDF Chat ✅
- [ ] PDF file works in chat
- [ ] DOCX file works in chat
- [ ] XLSX file works in chat
- [ ] PPTX file works in chat

### Document Insights ✅
- [ ] Section appears (📁)
- [ ] 3-5 insights generated
- [ ] At least 1 references document name
- [ ] Mentions dependencies, conflicts, risks

### Decision Paths ✅
- [ ] 3-4 paths generated (🗺️)
- [ ] Each has: rationale, teams, trade-offs, risk
- [ ] Paths are selectable
- [ ] Selection updates sections

### Scenarios ✅
- [ ] 3 scenarios generated (🔮)
- [ ] Each has: risks, dependencies, timeline, friction, consequences
- [ ] Scenarios are selectable
- [ ] Selection updates role goals

### Role Goals ✅
- [ ] 5-10 roles generated (👥)
- [ ] Each references scenario & dependencies
- [ ] Leadership language (not OKR)
- [ ] Export button appears
- [ ] Export downloads Excel file

### Alignment Brief ✅
- [ ] Button appears after final outcome
- [ ] Brief generates successfully
- [ ] Has all 7 elements:
  - [ ] Decision chosen
  - [ ] Scenario selected
  - [ ] Top 3 risks
  - [ ] Top 3 dependencies
  - [ ] Teams impacted
  - [ ] Alignment score
  - [ ] Recommended next step

---

## 🔍 Key Verification Points

### Document Insights Must Have:
✅ At least 1 insight mentions document name  
✅ Mentions dependencies  
✅ Mentions conflicting priorities  
✅ Mentions strategic anchors  
✅ Mentions misalignment risks

### Decision Paths Must Have:
✅ Exactly 3-4 paths (not 2, not 5+)  
✅ Rationale (why this path)  
✅ Teams impacted (specific names)  
✅ Trade-offs (gained vs. lost)  
✅ Risk level (Low/Med/High)

### Scenarios Must Have:
✅ All 3 scenarios (Acceleration, Expected, Risk)  
✅ Key risks (bullet points)  
✅ Cross-team dependencies  
✅ Timeline impact  
✅ Role friction points  
✅ Operational consequences

### Role Goals Must Have:
✅ 5-10 roles  
✅ Each references selected scenario  
✅ Each references at least 1 dependency  
✅ Leadership language (not OKR format)  
✅ Unique per role (not template)

### Alignment Brief Must Have:
✅ Decision chosen  
✅ Scenario selected  
✅ Top 3 risks  
✅ Top 3 dependencies  
✅ Teams impacted  
✅ Alignment score (Low/Med/High)  
✅ Recommended next step

---

## 🐛 Common Issues & Quick Fixes

| Issue | Quick Fix |
|-------|-----------|
| Routes not working | `php artisan route:clear` |
| Export button missing | Check browser console, hard refresh |
| Parsing fails | Check logs, verify API keys |
| Brief not generating | Check OpenAI API key in .env |
| File upload errors | Check file size/type validation |

---

## 📊 Expected Response Structure

```
🧩 Chat Acknowledgement
📁 Document Insights          ← 3-5 insights, doc references
📊 Goal Assessment Summary
📈 Scoring
🗺️ Strategy Map               ← 3-4 paths, all elements
🔮 Scenario Simulations       ← 3 scenarios, all elements
👥 Rephrased Goals by Role    ← 5-10 roles, export button
📌 Complementary Goals
✅ Final Outcome Summary      ← Brief button here
```

---

## 📁 Test Files Available

1. **DETAILED_TESTING_GUIDE.md** - Complete step-by-step (1,368 lines)
2. **TESTING_CHECKLIST.md** - Comprehensive checklist
3. **QUICK_TEST_GUIDE.md** - Quick testing steps
4. **TESTING_QUICK_REFERENCE.md** - This file
5. **verify_implementation.php** - Automated verification

---

## ✅ Success Indicators

- All file types upload ✅
- All file types parse ✅
- PDF Chat works with all types ✅
- Document insights reference docs ✅
- Decision paths have all elements ✅
- Scenarios have all elements ✅
- Role goals export works ✅
- Alignment brief generates ✅
- No critical errors ✅

---

**For detailed instructions, see: DETAILED_TESTING_GUIDE.md**

