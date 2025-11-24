<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AiChatCategoryTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('ai_chat_categories')->delete();

        \DB::table('ai_chat_categories')->insert(array(
            0 =>
            array(
                'id'    => 1,
                'name' => 'Ai Chat Bot',
                'short_name' => 'DC',
                'slug' => 'default',
                'description' => 'Chat With AI',
                'role' => 'default',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => '',
                'avatar' => 'backend/assets/img/expertise/1.jpg'
            ),
            array(
                'id' => 2,
                'name' => 'Mr Kevin',
                'short_name' => 'MK',
                'slug' => 'seo-expert',
                'description' => 'Chat With SEO Expert',
                'role' => 'SEO Expert',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will assist you to generate better the seo contents',
                'avatar' => 'backend/assets/img/expertise/2.jpg'
            ),
            array(
                'id' => 3,
                'name' => 'Tara Prater',
                'short_name' => 'TP',
                'slug' => 'cyber-expert',
                'description' => 'Chat With Cybersecurity Expert',
                'role' => 'Cybersecurity Expert',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will guide you for the security of your online world',
                'avatar' => 'backend/assets/img/expertise/3.jpg'
            ),
            array(
                'id' => 4,
                'name' => 'Carol Vizcarra',
                'short_name' => 'CV',
                'slug' => 'career-consultant',
                'description' => 'Chat With Career Consultant',
                'role' => 'Career Consultant',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will guide you to be more successful in your career',
                'avatar' => 'backend/assets/img/expertise/4.jpg'
            ),
            array(
                'id' => 5,
                'name' => 'Megan Hyde',
                'short_name' => 'MH',
                'slug' => 'accountant',
                'description' => 'Chat With Accountant',
                'role' => 'Accountant',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will answer your accounting related questions',
                'avatar' => 'backend/assets/img/expertise/5.jpg'
            ),
            array(
                'id' => 6,
                'name' => 'Flossie Cardoza',
                'short_name' => 'FC',
                'slug' => 'mbbs-doctor',
                'description' => 'Chat With MBBS Doctor',
                'role' => 'MBBS Doctor',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you to have a healthy life',
                'avatar' => 'backend/assets/img/expertise/6.jpg'
            ),
            array(
                'id' => 7,
                'name' => 'Matthew Smith',
                'short_name' => 'MS',
                'slug' => 'Interior-Designer',
                'description' => 'Chat With Interior Designer',
                'role' => 'Interior Designer',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you to decorate your lifestyle',
                'avatar' => 'backend/assets/img/expertise/7.jpg'
            ),
            array(
                'id' => 8,
                'name' => 'Mary Easley',
                'short_name' => 'ME',
                'slug' => 'Business-Consultant',
                'description' => 'Chat With Business Consultant',
                'role' => 'Business Consultant',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you to expand your business',
                'avatar' => 'backend/assets/img/expertise/8.jpg'
            ),
            array(
                'id' => 9,
                'name' => 'Tammy Allen',
                'short_name' => 'TA',
                'slug' => 'Neurosurgery-Specialist',
                'description' => 'Chat With Neurosurgery Specialist',
                'role' => 'Neurosurgery Specialist',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will assist you with neurosurgery information',
                'avatar' => 'backend/assets/img/expertise/9.jpg'
            ),
            array(
                'id' => 10,
                'name' => 'Evan Vaughn',
                'short_name' => 'EV',
                'slug' => 'Language-Tutor',
                'description' => 'Chat With Language Tutor',
                'role' => 'Language Tutor',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you learning and developing your language skills',
                'avatar' => 'backend/assets/img/expertise/10.jpg'
            ),
            array(
                'id' => 11,
                'name' => 'Lottie Tuck',
                'short_name' => 'LT',
                'slug' => 'Travel-Guide',
                'description' => 'Chat With Lottie Tuck',
                'role' => 'Travel Guide',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you to locate your next travel',
                'avatar' => 'backend/assets/img/expertise/11.jpg'
            ),
            array(
                'id' => 12,
                'name' => 'William Wood',
                'short_name' => 'WW',
                'slug' => 'UI-UX-Designer',
                'description' => 'Chat With UI/UX Designer',
                'role' => 'UI/UX Designer',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you designing & creating user experiences for your website & applications',
                'avatar' => 'backend/assets/img/expertise/12.jpg'
            ),
            array(
                'id' => 13,
                'name' => 'Jason Ferrara',
                'short_name' => 'JF',
                'slug' => 'Web-Developer',
                'description' => 'Chat With Web Developer',
                'role' => 'Web Developer',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you developing your website & applications',
                'avatar' => 'backend/assets/img/expertise/13.jpg'
            ),
            array(
                'id' => 14,
                'name' => 'Henry Martin',
                'short_name' => 'HM',
                'slug' => 'Finance-Expert',
                'description' => 'Chat With Finance Expert',
                'role' => 'Finance Expert',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you in your finances',
                'avatar' => 'backend/assets/img/expertise/14.jpg'
            ),
            array(
                'id' => 15,
                'name' => 'Paul Teeter',
                'short_name' => 'PT',
                'slug' => 'Locksmith-Expert',
                'description' => 'Chat With Locksmith Expert',
                'role' => 'Locksmith Expert',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you to secure your assets',
                'avatar' => 'backend/assets/img/expertise/15.jpg'
            ),
            array(
                'id' => 16,
                'name' => 'Carl Oakes',
                'short_name' => 'CO',
                'slug' => 'Fitness-Trainer',
                'description' => 'Chat With Fitness Trainer',
                'role' => 'Fitness Trainer',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you to keep yourself healthy & fit',
                'avatar' => 'backend/assets/img/expertise/16.jpg'
            ),
            array(
                'id' => 17,
                'name' => 'Carl Oakes',
                'short_name' => 'CO',
                'slug' => 'Fitness-Trainer',
                'description' => 'Chat With Fitness Trainer',
                'role' => 'Fitness Trainer',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you to keep yourself healthy & fit',
                'avatar' => 'backend/assets/img/expertise/17.jpg'
            ),
            array(
                'id' => 18,
                'name' => 'Jeffery Ham',
                'short_name' => 'JH',
                'slug' => 'Motivational-Expert',
                'description' => 'Chat With Motivational Expert',
                'role' => 'Motivational Expert',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you to cheer you up to achieve your next goal',
                'avatar' => 'backend/assets/img/expertise/18.jpg'
            ),
            array(
                'id' => 19,
                'name' => 'Maria Hoy',
                'short_name' => 'MH',
                'slug' => 'Photographer',
                'description' => 'Chat With Photographer',
                'role' => 'Photographer',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you to capture photos with quality',
                'avatar' => 'backend/assets/img/expertise/19.jpg'
            ),
            array(
                'id' => 20,
                'name' => 'Julie Fields',
                'short_name' => 'JF',
                'slug' => 'Project-Manager',
                'description' => 'Chat With Project Manager',
                'role' => 'Project Manager',
                'user_name' => '',
                'type'=>'chat',
                'assists_with' => 'I will help you to manage your projects & team',
                'avatar' => 'backend/assets/img/expertise/20.jpg'
            ),
            array(
                'id' => 21,
                'name' => 'AI Vision',
                'short_name' => 'AV',
                'slug' => 'ai-vision',
                'description' => 'Chat With Image Expert',
                'role' => 'Image Expert',
                'user_name' => '',
                'type'=>'vision',
                'assists_with' => 'Hi, I am Image Expert.I will help you to understand your images',
                'avatar' => 'backend/assets/img/expertise/1.jpg'
            ),
            
        array(
            'id'    => 22,
            'name' => 'Chat Image Creator',
            'short_name' => 'AIC',
            'slug' => 'ai-image-chat',
            'description' => 'Chat With Image Expert',
            'role' => 'Image Expert',
            'user_name' => '',
            'type'=>'image',
            'assists_with' => 'Hi! I am Image Creator Expert. I can assist to generate image by user inputI will help you to create your Image',
            'avatar' => 'backend/assets/img/expertise/1.jpg'
        ),
        
        ));
    }
}
