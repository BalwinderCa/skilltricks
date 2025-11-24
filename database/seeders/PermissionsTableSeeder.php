<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('permissions')->delete();

        \DB::table('permissions')->insert(array(
            0 =>
            array('id' => 1, 'name' => 'dashboard', 'group_name' => 'dashboard', 'guard_name' => 'web'),

            array('id' => 2, 'name' => 'subscriptions', 'group_name' => 'subscriptions', 'guard_name' => 'web'),
            array('id' => 3, 'name' => 'subscriptions_histories', 'group_name' => 'subscriptions', 'guard_name' => 'web'),

            array('id' => 4, 'name' => 'folders', 'group_name' => 'folders', 'guard_name' => 'web'),
            array('id' => 5, 'name' => 'projects', 'group_name' => 'projects', 'guard_name' => 'web'),

            array('id' => 6, 'name' => 'templates', 'group_name' => 'templates', 'guard_name' => 'web'),
            array('id' => 7, 'name' => 'speech_to_text', 'group_name' => 'ai_tools', 'guard_name' => 'web'),
            array('id' => 8, 'name' => 'generate_images', 'group_name' => 'ai_tools', 'guard_name' => 'web'),
            array('id' => 9, 'name' => 'generate_code', 'group_name' => 'ai_tools', 'guard_name' => 'web'),

            array('id' => 10, 'name' => 'customers', 'group_name' => 'customers', 'guard_name' => 'web'),
            array('id' => 11, 'name' => 'ban_customers', 'group_name' => 'customers', 'guard_name' => 'web'),

            array('id' => 12, 'name' => 'all_staffs', 'group_name' => 'staffs', 'guard_name' => 'web'),
            array('id' => 13, 'name' => 'add_staffs', 'group_name' => 'staffs', 'guard_name' => 'web'),
            array('id' => 14, 'name' => 'edit_staffs', 'group_name' => 'staffs', 'guard_name' => 'web'),
            array('id' => 15, 'name' => 'delete_staffs', 'group_name' => 'staffs', 'guard_name' => 'web'),

            array('id' => 16, 'name' => 'contact_us_messages', 'group_name' => 'contact_us_messages', 'guard_name' => 'web'),

            array('id' => 17, 'name' => 'tags', 'group_name' => 'tags', 'guard_name' => 'web'),
            array('id' => 18, 'name' => 'add_tags', 'group_name' => 'tags', 'guard_name' => 'web'),
            array('id' => 19, 'name' => 'edit_tags', 'group_name' => 'tags', 'guard_name' => 'web'),
            array('id' => 20, 'name' => 'delete_tags', 'group_name' => 'tags', 'guard_name' => 'web'),

            array('id' => 21, 'name' => 'blogs', 'group_name' => 'blogs', 'guard_name' => 'web'),
            array('id' => 22, 'name' => 'add_blogs', 'group_name' => 'blogs', 'guard_name' => 'web'),
            array('id' => 23, 'name' => 'edit_blogs', 'group_name' => 'blogs', 'guard_name' => 'web'),
            array('id' => 24, 'name' => 'publish_blogs', 'group_name' => 'blogs', 'guard_name' => 'web'),
            array('id' => 25, 'name' => 'delete_blogs', 'group_name' => 'blogs', 'guard_name' => 'web'),

            array('id' => 26, 'name' => 'blog_categories', 'group_name' => 'blogs', 'guard_name' => 'web'),
            array('id' => 27, 'name' => 'add_blog_categories', 'group_name' => 'blogs', 'guard_name' => 'web'),
            array('id' => 28, 'name' => 'edit_blog_categories', 'group_name' => 'blogs', 'guard_name' => 'web'),
            array('id' => 29, 'name' => 'delete_blog_categories', 'group_name' => 'blogs', 'guard_name' => 'web'),

            array('id' => 30, 'name' => 'pages', 'group_name' => 'pages', 'guard_name' => 'web'),
            array('id' => 31, 'name' => 'add_pages', 'group_name' => 'pages', 'guard_name' => 'web'),
            array('id' => 32, 'name' => 'edit_pages', 'group_name' => 'pages', 'guard_name' => 'web'),
            array('id' => 33, 'name' => 'delete_pages', 'group_name' => 'pages', 'guard_name' => 'web'),

            array('id' => 34, 'name' => 'faqs', 'group_name' => 'faqs', 'guard_name' => 'web'),

            array('id' => 35, 'name' => 'media_manager', 'group_name' => 'media_manager', 'guard_name' => 'web'),
            array('id' => 36, 'name' => 'add_media', 'group_name' => 'media_manager', 'guard_name' => 'web'),
            array('id' => 37, 'name' => 'delete_media', 'group_name' => 'media_manager', 'guard_name' => 'web'),

            array('id' => 38, 'name' => 'newsletters', 'group_name' => 'newsletters', 'guard_name' => 'web'),
            array('id' => 39, 'name' => 'subscribers', 'group_name' => 'newsletters', 'guard_name' => 'web'),
            array('id' => 40, 'name' => 'delete_subscribers', 'group_name' => 'newsletters', 'guard_name' => 'web'),

            array('id' => 41, 'name' => 'open_ai', 'group_name' => 'open_ai', 'guard_name' => 'web'),

            array('id' => 42, 'name' => 'homepage', 'group_name' => 'appearance', 'guard_name' => 'web'),
            array('id' => 43, 'name' => 'header', 'group_name' => 'appearance', 'guard_name' => 'web'),
            array('id' => 44, 'name' => 'footer', 'group_name' => 'appearance', 'guard_name' => 'web'),

            array('id' => 45, 'name' => 'roles_and_permissions', 'group_name' => 'roles_and_permissions', 'guard_name' => 'web'),
            array('id' => 46, 'name' => 'add_roles_and_permissions', 'group_name' => 'roles_and_permissions', 'guard_name' => 'web'),
            array('id' => 47, 'name' => 'edit_roles_and_permissions', 'group_name' => 'roles_and_permissions', 'guard_name' => 'web'),
            array('id' => 48, 'name' => 'delete_roles_and_permissions', 'group_name' => 'roles_and_permissions', 'guard_name' => 'web'),

            array('id' => 49, 'name' => 'smtp_settings', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 50, 'name' => 'general_settings', 'group_name' => 'system_settings', 'guard_name' => 'web'),

            array('id' => 51, 'name' => 'currency_settings', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 52, 'name' => 'add_currency', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 53, 'name' => 'edit_currency', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 54, 'name' => 'publish_currency', 'group_name' => 'system_settings', 'guard_name' => 'web'),

            array('id' => 55, 'name' => 'language_settings', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 56, 'name' => 'add_languages', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 57, 'name' => 'edit_languages', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 58, 'name' => 'publish_languages', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 59, 'name' => 'translate_languages', 'group_name' => 'system_settings', 'guard_name' => 'web'),

            array('id' => 60, 'name' => 'payment_settings', 'group_name' => 'system_settings', 'guard_name' => 'web'),

            array('id' => 61, 'name' => 'default_language', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 62, 'name' => 'default_currency', 'group_name' => 'system_settings', 'guard_name' => 'web'),

            array('id' => 63, 'name' => 'social_login_settings', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 64, 'name' => 'auth_settings', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 65, 'name' => 'otp_settings', 'group_name' => 'system_settings', 'guard_name' => 'web'),

            // v1.1.0
            array('id' => 66, 'name' => 'affiliate_configurations', 'group_name' => 'affiliate_system', 'guard_name' => 'web'),
            array('id' => 67, 'name' => 'affiliate_withdraw', 'group_name' => 'affiliate_system', 'guard_name' => 'web'),
            array('id' => 68, 'name' => 'affiliate_earning_histories', 'group_name' => 'affiliate_system', 'guard_name' => 'web'),
            array('id' => 69, 'name' => 'affiliate_payment_histories', 'group_name' => 'affiliate_system', 'guard_name' => 'web'),

            // v1.5.0
            array('id' => 70, 'name' => 'custom_template_categories', 'group_name' => 'templates', 'guard_name' => 'web'),
            array('id' => 71, 'name' => 'custom_templates', 'group_name' => 'templates', 'guard_name' => 'web'),

            array('id' => 72, 'name' => 'words_report', 'group_name' => 'report', 'guard_name' => 'web'),
            array('id' => 73, 'name' => 'codes_report', 'group_name' => 'report', 'guard_name' => 'web'),
            array('id' => 74, 'name' => 'images_report', 'group_name' => 'report', 'guard_name' => 'web'),
            array('id' => 75, 'name' => 's2t_report', 'group_name' => 'report', 'guard_name' => 'web'),
            array('id' => 76, 'name' => 'most_used_templates', 'group_name' => 'report', 'guard_name' => 'web'),
            array('id' => 77, 'name' => 'subscriptions_reports', 'group_name' => 'report', 'guard_name' => 'web'),
            // array( 'id'  => 1, 'name' => 'affiliate_commission_reports', 'group_name' => 'report', 'guard_name' => 'web'),

            // v1.7.0
            array('id' => 78, 'name' => 'ai_chat', 'group_name' => 'ai_tools', 'guard_name' => 'web'),

            // v2.0.0
            array('id' => 79, 'name' => 'about_us_page', 'group_name' => 'appearance', 'guard_name' => 'web'),

            // v2.1.0
            array('id' => 80, 'name' => 'text_to_speech', 'group_name' => 'ai_tools', 'guard_name' => 'web'),

            //v2.3.0
            array('id' => 81, 'name' => 'multiOpenAi', 'group_name' => 'open_ai', 'guard_name' => 'web'),
            array('id' => 82, 'name' => 'add_multiOpenAi', 'group_name' => 'open_ai', 'guard_name' => 'web'),
            array('id' => 83, 'name' => 'edit_multiOpenAi', 'group_name' => 'open_ai', 'guard_name' => 'web'),
            array('id' => 84, 'name' => 'status_multiOpenAi', 'group_name' => 'open_ai', 'guard_name' => 'web'),
            array('id' => 85, 'name' => 'delete_multiOpenAi', 'group_name' => 'open_ai', 'guard_name' => 'web'),

            array('id' => 86, 'name' => 'voice_settings', 'group_name' => 'voice_settings', 'guard_name' => 'web'),

            // own staff
            array('id' => 87, 'name' => 'own_staff', 'group_name' => 'staffs', 'guard_name' => 'web'),
            // offline payment
            array('id' => 88, 'name' => 'offline_payment_methods', 'group_name' => 'offline_payment_methods', 'guard_name' => 'web'),
            array('id' => 89, 'name' => 'add_offline_payment_methods', 'group_name' => 'offline_payment_methods', 'guard_name' => 'web'),
            array('id' => 90, 'name' => 'edit_offline_payment_methods', 'group_name' => 'offline_payment_methods', 'guard_name' => 'web'),
            array('id' => 91, 'name' => 'delete_offline_payment_methods', 'group_name' => 'offline_payment_methods', 'guard_name' => 'web'),

            array('id' => 92, 'name' => 'all_payment_request', 'group_name' => 'payment_request', 'guard_name' => 'web'),
            array('id' => 93, 'name' => 'approve_payment_request', 'group_name' => 'payment_request', 'guard_name' => 'web'),
            array('id' => 94, 'name' => 'reject_payment_request', 'group_name' => 'payment_request', 'guard_name' => 'web'),
            array('id' => 95, 'name' => 'add_note_payment_request', 'group_name' => 'payment_request', 'guard_name' => 'web'),

            // added by shohan
            array('id' => 96, 'name' => 'add_customer', 'group_name' => 'customers', 'guard_name' => 'web'),

            array('id' => 97, 'name' => 'cron_job', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 98, 'name' => 'storage_manager', 'group_name' => 'system_settings', 'guard_name' => 'web'),

            // added by shohan
            array('id' => 99, 'name' => 'blog_wizard', 'group_name' => 'blog_wizard', 'guard_name' => 'web'),
            //
            array('id' => 100, 'name' => 'subscriptions_settings', 'group_name' => 'subscriptions', 'guard_name' => 'web'),
            array('id' => 101, 'name' => 'add_folders', 'group_name' => 'folders', 'guard_name' => 'web'),
            array('id' => 102, 'name' => 'text_to_speech_eleven_labs', 'group_name' => 'ai_tools', 'guard_name' => 'web'),
            array('id' => 103, 'name' => 'pwa_settings', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 104, 'name' => 'ai_vision', 'group_name' => 'ai_tools', 'guard_name' => 'web'),
            array('id' => 105, 'name' => 'ai_image_chat', 'group_name' => 'ai_tools', 'guard_name' => 'web'),
            array('id' => 106, 'name' => 'email_template', 'group_name' => 'system_settings', 'guard_name' => 'web'),

            // AI Pdf Chat
            array('id' => 107, 'name' => 'ai_pdf_chat', 'group_name' => 'ai_tools', 'guard_name' => 'web'),
            array('id' => 108, 'name' => 'fine_tune', 'group_name' => 'ai_tools', 'guard_name' => 'web'),
            array('id' => 109, 'name' => 'ai_rewriter', 'group_name' => 'ai_tools', 'guard_name' => 'web'),

            // AI Images
            array('id' => 110, 'name' => 'dall_e_2', 'group_name' => 'ai_images', 'guard_name' => 'web'),
            array('id' => 111, 'name' => 'dall_e_3', 'group_name' => 'ai_images', 'guard_name' => 'web'),
            array('id' => 112, 'name' => 'stable_diffusion', 'group_name' => 'ai_images', 'guard_name' => 'web'),
            array('id' => 113, 'name' => 'popular_templates', 'group_name' => 'templates', 'guard_name' => 'web'),
            array('id' => 114, 'name' => 'favorites_templates', 'group_name' => 'templates', 'guard_name' => 'web'),
            array('id' => 115, 'name' => 'support_category', 'group_name' => 'supports', 'guard_name' => 'web'),
            array('id' => 116, 'name' => 'support_ticket', 'group_name' => 'supports', 'guard_name' => 'web'),
            array('id' => 117, 'name' => 'support_priority', 'group_name' => 'supports', 'guard_name' => 'web'),
            array('id' => 118, 'name' => 'theme_settings', 'group_name' => 'appearance', 'guard_name' => 'web'),
            array('id' => 119, 'name' => 'invoice_settings', 'group_name' => 'system_settings', 'guard_name' => 'web'),
            array('id' => 120, 'name' => 'plagiarism_api', 'group_name' => 'plagiarism', 'guard_name' => 'web'),
            array('id' => 121, 'name' => 'ai_plagiarism', 'group_name' => 'plagiarism', 'guard_name' => 'web'),
            array('id' => 122, 'name' => 'ai_detector', 'group_name' => 'plagiarism', 'guard_name' => 'web'),
            array('id' => 123, 'name' => 'add_content_detectors', 'group_name' => 'plagiarism', 'guard_name' => 'web'),
            array('id' => 124, 'name' => 'add_content_plagiarism', 'group_name' => 'plagiarism', 'guard_name' => 'web'),
        ));
    }
}
