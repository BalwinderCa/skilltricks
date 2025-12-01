<?php
/**
 * Quick Verification Script
 * Run: php verify_implementation.php
 */

echo "🔍 Verifying Implementation...\n\n";

$checks = [];
$errors = [];

// Check 1: DocumentParserService exists
$file = __DIR__ . '/app/Http/Services/DocumentParserService.php';
if (file_exists($file)) {
    $checks[] = "✅ DocumentParserService.php exists";
} else {
    $errors[] = "❌ DocumentParserService.php NOT FOUND";
}

// Check 2: RoleGoalsExport exists
$file = __DIR__ . '/app/Exports/RoleGoalsExport.php';
if (file_exists($file)) {
    $checks[] = "✅ RoleGoalsExport.php exists";
} else {
    $errors[] = "❌ RoleGoalsExport.php NOT FOUND";
}

// Check 3: Export view exists
$file = __DIR__ . '/resources/views/exports/role_goals_export.blade.php';
if (file_exists($file)) {
    $checks[] = "✅ role_goals_export.blade.php exists";
} else {
    $errors[] = "❌ role_goals_export.blade.php NOT FOUND";
}

// Check 4: Controller has new methods
$file = __DIR__ . '/app/Http/Controllers/Backend/AI/AiChatController.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    if (strpos($content, 'generate_leadership_alignment_brief') !== false) {
        $checks[] = "✅ generate_leadership_alignment_brief method exists";
    } else {
        $errors[] = "❌ generate_leadership_alignment_brief method NOT FOUND";
    }
    
    if (strpos($content, 'export_role_goals') !== false) {
        $checks[] = "✅ export_role_goals method exists";
    } else {
        $errors[] = "❌ export_role_goals method NOT FOUND";
    }
    
    if (strpos($content, 'parseRoleGoalsFromText') !== false) {
        $checks[] = "✅ parseRoleGoalsFromText method exists";
    } else {
        $errors[] = "❌ parseRoleGoalsFromText method NOT FOUND";
    }
} else {
    $errors[] = "❌ AiChatController.php NOT FOUND";
}

// Check 5: DocumentsController updated
$file = __DIR__ . '/app/Http/Controllers/Backend/DocumentsController.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    if (strpos($content, 'mimes:pdf,doc,docx,xlsx,xls,pptx,ppt') !== false) {
        $checks[] = "✅ DocumentsController accepts new file types";
    } else {
        $errors[] = "❌ DocumentsController validation NOT UPDATED";
    }
    
    if (strpos($content, 'DocumentParserService') !== false) {
        $checks[] = "✅ DocumentsController uses DocumentParserService";
    } else {
        $errors[] = "❌ DocumentsController NOT using DocumentParserService";
    }
} else {
    $errors[] = "❌ DocumentsController.php NOT FOUND";
}

// Check 6: PdfService updated
$file = __DIR__ . '/app/Services/Pdf/PdfService.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    if (strpos($content, 'DocumentParserService') !== false) {
        $checks[] = "✅ PdfService uses DocumentParserService";
    } else {
        $errors[] = "❌ PdfService NOT using DocumentParserService";
    }
} else {
    $errors[] = "❌ PdfService.php NOT FOUND";
}

// Check 7: Routes exist
$file = __DIR__ . '/routes/backend.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    if (strpos($content, 'users-new-chat-generate-alignment-brief') !== false) {
        $checks[] = "✅ Alignment brief route exists";
    } else {
        $errors[] = "❌ Alignment brief route NOT FOUND";
    }
    
    if (strpos($content, 'users-new-chat-export-role-goals') !== false) {
        $checks[] = "✅ Export role goals route exists";
    } else {
        $errors[] = "❌ Export role goals route NOT FOUND";
    }
} else {
    $errors[] = "❌ routes/backend.php NOT FOUND";
}

// Check 8: View updated
$file = __DIR__ . '/resources/views/backend/pages/aiChat/users-new-chat.blade.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    if (strpos($content, 'exportRoleGoals') !== false) {
        $checks[] = "✅ Export function in view";
    } else {
        $errors[] = "❌ Export function NOT in view";
    }
    
    if (strpos($content, 'generateLeadershipAlignmentBrief') !== false) {
        $checks[] = "✅ Alignment brief function in view";
    } else {
        $errors[] = "❌ Alignment brief function NOT in view";
    }
} else {
    $errors[] = "❌ users-new-chat.blade.php NOT FOUND";
}

// Check 9: PDF Chat views updated
$file = __DIR__ . '/resources/views/backend/pages/pdfChat/form-pdf.blade.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    if (strpos($content, 'accept=".pdf,.doc,.docx,.xlsx,.xls,.pptx,.ppt"') !== false) {
        $checks[] = "✅ PDF Chat form accepts new file types";
    } else {
        $errors[] = "❌ PDF Chat form NOT updated";
    }
} else {
    $errors[] = "❌ form-pdf.blade.php NOT FOUND";
}

// Print results
echo "📋 Verification Results:\n\n";
foreach ($checks as $check) {
    echo $check . "\n";
}

if (count($errors) > 0) {
    echo "\n⚠️  Issues Found:\n\n";
    foreach ($errors as $error) {
        echo $error . "\n";
    }
    echo "\n❌ Verification FAILED\n";
    exit(1);
} else {
    echo "\n✅ All checks passed!\n";
    echo "\n🎉 Implementation verified successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Clear caches: php artisan cache:clear && php artisan route:clear\n";
    echo "2. Test document uploads\n";
    echo "3. Test AI chat features\n";
    echo "4. Test export functionality\n";
    exit(0);
}

