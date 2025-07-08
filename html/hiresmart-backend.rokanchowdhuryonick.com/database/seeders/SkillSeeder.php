<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $skills = [
            // Programming Languages
            'PHP', 'JavaScript', 'Python', 'Java', 'C#', 'C++', 'Go', 'Rust', 
            'TypeScript', 'Ruby', 'Swift', 'Kotlin',

            // Web Frameworks & Libraries
            'Laravel', 'React', 'Vue.js', 'Angular', 'Node.js', 'Express.js', 
            'Django', 'Flask', 'Spring Boot', 'ASP.NET', 'Next.js', 'Nuxt.js',

            // Mobile Development
            'React Native', 'Flutter', 'iOS Development', 'Android Development', 'Xamarin',

            // Databases
            'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'SQLite', 'Oracle', 
            'SQL Server', 'Elasticsearch', 'Cassandra',

            // DevOps & Cloud
            'Docker', 'Kubernetes', 'AWS', 'Azure', 'Google Cloud', 'Jenkins', 
            'GitLab CI', 'GitHub Actions', 'Terraform', 'Ansible', 'Linux', 'Nginx', 'Apache',

            // Data Science & AI
            'Machine Learning', 'Deep Learning', 'TensorFlow', 'PyTorch', 'Pandas', 
            'NumPy', 'Scikit-learn', 'Data Analysis', 'Data Visualization', 'Power BI', 'Tableau',

            // Version Control & Tools
            'Git', 'GitHub', 'GitLab', 'Bitbucket', 'Jira', 'Trello', 'Slack', 
            'Figma', 'Adobe Photoshop', 'Adobe Illustrator',

            // Testing
            'Unit Testing', 'Integration Testing', 'Test Driven Development', 
            'PHPUnit', 'Jest', 'Cypress', 'Selenium',

            // Soft Skills
            'Team Leadership', 'Project Management', 'Agile/Scrum', 'Communication', 
            'Problem Solving', 'Critical Thinking', 'Time Management', 'Adaptability', 
            'Creativity', 'Attention to Detail',

            // Business Skills
            'Business Analysis', 'Requirements Analysis', 'Digital Marketing', 'SEO', 
            'Content Marketing', 'Social Media Marketing', 'Sales', 'Customer Service', 
            'Financial Analysis', 'Accounting',

            // Security
            'Cybersecurity', 'Penetration Testing', 'Network Security', 'Information Security',

            // Other Technical Skills
            'API Development', 'REST API', 'GraphQL', 'Microservices', 'System Design', 
            'Database Design', 'Performance Optimization', 'Code Review',
        ];

        // Get existing skill names to avoid duplicates
        $existingSkills = Skill::pluck('name')->toArray();
        
        // Filter out skills that already exist
        $newSkills = array_diff($skills, $existingSkills);
        
        // Only insert if there are new skills
        if (!empty($newSkills)) {
            $skillData = array_map(fn($name) => [
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now()
            ], $newSkills);
            
            Skill::insert($skillData);
        }

        $this->command->info('✅ Skills seeded successfully: ' . count($skills) . ' skills created');
    }
} 