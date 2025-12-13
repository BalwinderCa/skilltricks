# API Calls and Button Disable Flow Documentation

## Overview
This document explains the complete flow of API calls and Next button state management in the AI Chat step-by-step navigation.

---

## 🔄 Complete Flow

### **Initial Setup (When First Response Arrives)**
1. **Location**: `users-new-chat.blade.php` - After first AI response
2. **What Happens**:
   - Response is split into sections (🧩, 📁, 📊, 📈, 🗺️, 🔮, 👥, 📌, ✅)
   - Strategy points and scenario options are extracted
   - **NO API CALLS YET** - Just parsing
   - **NO BUTTON DISABLING** - First Next button is always enabled

---

### **Step 0: First Section (e.g., Document Insights)**
1. **Location**: `renderStep()` function - `currentStep === 0`
2. **API Calls**: ❌ None
3. **Button State**: ✅ **Always Enabled**
   - Code: `if (currentStep === 0) { nextBtn.disabled = false; return; }`
4. **User Action**: Click "Next" → Goes to Step 1

---

### **Step 1: Strategy Map Page (🗺️)**
1. **Location**: `renderStep()` function - `currentStep === strategyMapIndex`
2. **What Happens When User Reaches This Page**:
   - Strategy options are displayed with radio buttons
   - **TRIGGERS**: `eagerLoadAllStrategies()` function starts
   
3. **API Calls Made**:
   - **Function**: `eagerLoadAllStrategies()` (Line ~1280)
   - **Endpoint**: `/dashboard/users-new-chat-update-strategy`
   - **When**: Immediately when user reaches strategy page
   - **What**: Fetches responses for ALL strategy options in parallel
   - **Tracking**:
     ```javascript
     window.pendingApiCalls++;  // Increment for each strategy
     window.apiCallInProgress = true;
     updateNextButtonState();   // Called but doesn't disable current page
     ```
   - **After Each Call**:
     ```javascript
     window.pendingApiCalls--;  // Decrement
     window.apiCallInProgress = window.pendingApiCalls > 0;
     updateNextButtonState();   // Update button state
     ```

4. **Button State Logic**:
   - **Current Page (Step 1)**: ✅ **Enabled** (even during API calls)
   - **Next Page (Step 2)**: 
     - Checks: `isNextStepDataReady(nextStepIndex)` 
     - If next step needs strategy data AND it's not cached → ❌ **Disabled**
     - Shows: "Loading..." text
     - Code: `if (!dataReady) { nextBtn.disabled = true; }`

5. **User Action**: 
   - User can select a strategy (radio button)
   - **When Strategy Selected**:
     - **API Call**: `/dashboard/users-new-chat-update-strategy` (Line ~1790)
     - **Purpose**: Save selection to database
     - **Tracking**: `pendingApiCalls++` → `updateNextButtonState()` → `pendingApiCalls--`
     - **Button Update**: `updateNextButtonState()` called after selection

---

### **Step 2: Scenario Selection Page (🔮)**
1. **Location**: `renderStep()` function - `currentStep === scenarioIndex`
2. **What Happens When User Reaches This Page**:
   - Scenario options are displayed with radio buttons
   - **TRIGGERS**: `eagerLoadAllScenarios()` function starts
   
3. **API Calls Made**:
   - **Function**: `eagerLoadAllScenarios()` (Line ~1383)
   - **Endpoint**: `/dashboard/users-new-chat-update-scenario`
   - **When**: Immediately when user reaches scenario page
   - **What**: Fetches responses for ALL scenario options in parallel
   - **Tracking**:
     ```javascript
     window.pendingApiCalls++;  // Increment for each scenario
     window.apiCallInProgress = true;
     updateNextButtonState();   // Called but doesn't disable current page
     ```
   - **After Each Call**:
     ```javascript
     window.pendingApiCalls--;  // Decrement
     window.apiCallInProgress = window.pendingApiCalls > 0;
     updateNextButtonState();   // Update button state
     ```

4. **Button State Logic**:
   - **Current Page (Step 2)**: ✅ **Enabled** (even during API calls)
   - **Next Page (Step 3)**: 
     - Checks: `isNextStepDataReady(nextStepIndex)`
     - If next step needs scenario data AND it's not cached → ❌ **Disabled**
     - Shows: "Loading..." text

5. **User Action**: 
   - User can select a scenario (radio button)
   - **When Scenario Selected**:
     - **API Call**: `fetchScenarioResponse()` (Line ~1483)
     - **Endpoint**: `/dashboard/users-new-chat-update-scenario`
     - **Purpose**: Fetch scenario-specific content
     - **Tracking**: `pendingApiCalls++` → `updateNextButtonState()` → `pendingApiCalls--`
     - **Button Update**: `updateNextButtonState()` called after data loads

---

### **Step 3+: Subsequent Pages (👥, 📌, ✅)**
1. **Location**: `renderStep()` function - `currentStep > scenarioIndex`
2. **API Calls**: ❌ None (uses cached data)
3. **Button State Logic**:
   - **Current Page**: ✅ **Enabled**
   - **Next Page**: 
     - Checks: `isNextStepDataReady(nextStepIndex)`
     - If next step needs strategy/scenario data AND it's not cached → ❌ **Disabled**
     - Otherwise → ✅ **Enabled**

---

## 🔍 Key Functions

### **1. `updateNextButtonState()` (Line ~1237)**
**Purpose**: Updates Next button enabled/disabled state

**Logic**:
```javascript
1. If currentStep === 0 → Always enable
2. If nextStepIndex >= sections.length → Always enable (last step)
3. Check: isNextStepDataReady(nextStepIndex)
   - If false → Disable button, show "Loading..."
   - If true → Enable button, show "Next" or "Finish"
```

**Called From**:
- After each API call completes (in `finally` blocks)
- After strategies finish loading
- After scenarios finish loading
- After strategy selection
- After scenario selection
- After `renderStep()` completes

---

### **2. `isNextStepDataReady(nextStepIndex)` (Line ~1210)**
**Purpose**: Checks if the NEXT step's required data is ready

**Logic**:
```javascript
1. If nextStepIndex > strategyMapIndex:
   - Check if selectedStrategy exists
   - Check if strategyResponsesCache[selectedStrategy] exists
   - If not → return false

2. If nextStepIndex > scenarioIndex:
   - Check if selectedScenario exists
   - Check if scenarioResponsesCache[selectedScenario] exists
   - If not → return false

3. Return true (data is ready)
```

---

### **3. `eagerLoadAllStrategies()` (Line ~1280)**
**Purpose**: Pre-load all strategy responses when user reaches strategy page

**Flow**:
1. Check if already loading/loaded → return early
2. Set `isLoadingStrategies = true`
3. For each strategy point:
   - `pendingApiCalls++`
   - `apiCallInProgress = true`
   - `updateNextButtonState()` (doesn't disable current page)
   - Fetch `/dashboard/users-new-chat-update-strategy`
   - Store in `strategyResponsesCache[point]`
   - `pendingApiCalls--`
   - `updateNextButtonState()` (updates button state)
4. After all complete: `updateNextButtonState()`

---

### **4. `eagerLoadAllScenarios()` (Line ~1383)**
**Purpose**: Pre-load all scenario responses when user reaches scenario page

**Flow**:
1. Check if already loading/loaded → return early
2. Set `isLoadingScenarios = true`
3. For each scenario option:
   - `pendingApiCalls++`
   - `apiCallInProgress = true`
   - `updateNextButtonState()` (doesn't disable current page)
   - Fetch `/dashboard/users-new-chat-update-scenario`
   - Store in `scenarioResponsesCache[scenarioText]`
   - `pendingApiCalls--`
   - `updateNextButtonState()` (updates button state)
4. After all complete: `updateNextButtonState()`

---

### **5. `fetchScenarioResponse(scenarioText, isUserSelection)` (Line ~1483)**
**Purpose**: Fetch a single scenario response (when user selects or when loading active scenario)

**Flow**:
1. Check cache → if exists, return early
2. `pendingApiCalls++`
3. `apiCallInProgress = true`
4. `updateNextButtonState()`
5. Fetch `/dashboard/users-new-chat-update-scenario`
6. Store in `scenarioResponsesCache[scenarioText]`
7. `updateNextButtonState()` (after data loads)
8. `pendingApiCalls--` (in finally block)
9. `updateNextButtonState()` (final update)

---

## 📊 API Call Tracking Variables

### **`window.pendingApiCalls`**
- **Type**: Number
- **Purpose**: Count of active API calls
- **Incremented**: Before each API call
- **Decremented**: In `finally` block after each API call
- **Used**: To track if any calls are in progress

### **`window.apiCallInProgress`**
- **Type**: Boolean
- **Purpose**: Flag indicating if any API calls are active
- **Set**: `apiCallInProgress = pendingApiCalls > 0`
- **Note**: Currently NOT used in button disable logic (removed to fix issue)

---

## 🎯 Button Disable Logic Summary

### **When Button is DISABLED**:
1. ✅ Next step's data is NOT ready (checked via `isNextStepDataReady()`)
2. ❌ NOT disabled based on current page's API calls (this was the bug)

### **When Button is ENABLED**:
1. ✅ First step (currentStep === 0)
2. ✅ Last step (nextStepIndex >= sections.length)
3. ✅ Next step's data IS ready (checked via `isNextStepDataReady()`)

---

## 🔄 Complete User Journey Example

### **User Flow**:
1. **Step 0**: User sees first section
   - Next button: ✅ Enabled
   - API calls: None
   - User clicks "Next"

2. **Step 1**: User sees Strategy Map
   - Next button: ✅ Enabled (even during loading)
   - API calls: `eagerLoadAllStrategies()` starts (all strategies in parallel)
   - Next button for Step 2: ❌ Disabled (if Step 2 needs strategy data that's not ready)
   - User selects a strategy
   - API call: Save strategy selection to DB
   - Next button for Step 2: ✅ Enabled (when strategy data is ready)
   - User clicks "Next"

3. **Step 2**: User sees Scenario Selection
   - Next button: ✅ Enabled (even during loading)
   - API calls: `eagerLoadAllScenarios()` starts (all scenarios in parallel)
   - Next button for Step 3: ❌ Disabled (if Step 3 needs scenario data that's not ready)
   - User selects a scenario
   - API call: Fetch scenario-specific content
   - Next button for Step 3: ✅ Enabled (when scenario data is ready)
   - User clicks "Next"

4. **Step 3+**: User sees subsequent sections
   - Next button: ✅ Enabled (uses cached data)
   - API calls: None
   - User clicks "Next" until "Finish"

---

## 🐛 Bug Fix Applied

### **Previous Issue**:
- Button was disabled on current page when API calls were happening
- Code: `if (!dataReady || hasPendingCalls) { disable button }`

### **Fixed**:
- Button only disabled if next step's data isn't ready
- Code: `if (!dataReady) { disable button }`
- Removed `hasPendingCalls` check from button disable logic

---

## 📝 Notes

1. **Eager Loading**: Data loads when user reaches relevant page, not at start
2. **Progressive Loading**: Data is cached as it loads, UI updates progressively
3. **Button State**: Only checks next step, not current step's API calls
4. **Caching**: All API responses are cached to avoid duplicate calls
5. **Database Saving**: Strategy/scenario selections are saved to DB for persistence



