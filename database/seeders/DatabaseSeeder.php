<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [];
        $demoUsersData = [
            ['google_id' => 'demo_1', 'name' => 'Alice Chen', 'email' => 'alice@demo.example', 'avatar_url' => null],
            ['google_id' => 'demo_2', 'name' => 'Bob Martinez', 'email' => 'bob@demo.example', 'avatar_url' => null],
            ['google_id' => 'demo_3', 'name' => 'Carol Williams', 'email' => 'carol@demo.example', 'avatar_url' => null],
        ];

        foreach ($demoUsersData as $ud) {
            $users[] = User::firstOrCreate(['google_id' => $ud['google_id']], $ud);
        }

        $this->createEnglishVocabQuiz($users[0]);
        $this->createScientificTermsQuiz($users[1]);
        $this->createFrenchVocabQuiz($users[0]);
        $this->createMedicalTermsQuiz($users[2]);
        $this->createBusinessVocabQuiz($users[1]);
    }

    private function createEnglishVocabQuiz(User $user): void
    {
        $quiz = Quiz::create([
            'ulid' => strtolower(Str::ulid()),
            'user_id' => $user->id,
            'title' => 'Advanced English Vocabulary',
            'description' => 'Test your knowledge of sophisticated English words. Each question challenges you to identify the correct definition.',
            'mode' => 'casual',
            'seconds_per_question' => 8,
            'status' => 'published',
            'is_listed' => true,
            'published_at' => now()->subDays(10),
            'runs_count' => 42,
            'passes_count' => 12,
            'reached_sum' => 320,
        ]);

        $questions = [
            ['prompt' => 'What does "Ephemeral" mean?', 'options' => ['Lasting for a very short time', 'Extremely large', 'Deeply mysterious', 'Highly practical'], 'correct' => 0],
            ['prompt' => 'What does "Obfuscate" mean?', 'options' => ['To make clear and transparent', 'To render obscure or unclear', 'To speed up a process', 'To strengthen a structure'], 'correct' => 1],
            ['prompt' => 'What does "Perspicacious" mean?', 'options' => ['Easily angered', 'Prone to sweating', 'Having a ready insight; shrewd', 'Lacking confidence'], 'correct' => 2],
            ['prompt' => 'What does "Loquacious" mean?', 'options' => ['Silent and withdrawn', 'Tending to talk a great deal', 'Extremely honest', 'Visually stunning'], 'correct' => 1],
            ['prompt' => 'What does "Equanimity" mean?', 'options' => ['Mental calmness under stress', 'Extreme anger', 'Mathematical equality', 'Physical exhaustion'], 'correct' => 0],
            ['prompt' => 'What does "Perfidious" mean?', 'options' => ['Perfect in every way', 'Guilty of betrayal; treacherous', 'Overly cautious', 'Pleasantly fragrant'], 'correct' => 1],
            ['prompt' => 'What does "Sycophant" mean?', 'options' => ['A type of musical instrument', 'An ancient writing system', 'A person who flatters to gain advantage', 'A mathematical theorem'], 'correct' => 2],
            ['prompt' => 'What does "Recalcitrant" mean?', 'options' => ['Easily managed', 'Having an unpleasant smell', 'Stubbornly resistant to authority', 'Highly educated'], 'correct' => 2],
        ];

        foreach ($questions as $i => $q) {
            $quiz->questions()->create(['position' => $i, 'prompt' => $q['prompt'], 'options' => $q['options'], 'correct_option_index' => $q['correct']]);
        }
    }

    private function createScientificTermsQuiz(User $user): void
    {
        $quiz = Quiz::create([
            'ulid' => strtolower(Str::ulid()),
            'user_id' => $user->id,
            'title' => 'Scientific Terminology',
            'description' => 'From biology to physics — test your scientific vocabulary.',
            'mode' => 'strict',
            'seconds_per_question' => 10,
            'status' => 'published',
            'is_listed' => true,
            'published_at' => now()->subDays(7),
            'runs_count' => 28,
            'passes_count' => 8,
            'reached_sum' => 185,
        ]);

        $questions = [
            ['prompt' => 'What is "Osmosis"?', 'options' => ['Movement of solutes across a membrane', 'Movement of water across a semi-permeable membrane', 'The process of cellular division', 'The breakdown of glucose'], 'correct' => 1],
            ['prompt' => 'What does "Mitosis" produce?', 'options' => ['Four genetically unique cells', 'Two genetically identical cells', 'One specialized cell', 'Three identical cells'], 'correct' => 1],
            ['prompt' => 'What is "Entropy" in physics?', 'options' => ['A measure of orderliness in a system', 'A measure of disorder or randomness', 'The energy stored in chemical bonds', 'The rate of heat transfer'], 'correct' => 1],
            ['prompt' => 'What is "Photosynthesis"?', 'options' => ['Breaking down sugars for energy', 'The creation of light from cells', 'Converting light energy into chemical energy', 'The process of oxygen absorption'], 'correct' => 2],
            ['prompt' => 'What does "Homeostasis" mean?', 'options' => ['Rapid cell growth', 'Maintenance of a stable internal environment', 'The study of ecosystems', 'Movement towards a stimulus'], 'correct' => 1],
            ['prompt' => 'What is a "Catalyst"?', 'options' => ['A substance that slows chemical reactions', 'A product of chemical reactions', 'A substance that speeds up reactions without being consumed', 'A measure of reaction temperature'], 'correct' => 2],
            ['prompt' => 'What is "DNA"?', 'options' => ['A type of protein', 'Deoxyribonucleic acid — the hereditary material', 'A cellular membrane', 'A type of RNA'], 'correct' => 1],
        ];

        foreach ($questions as $i => $q) {
            $quiz->questions()->create(['position' => $i, 'prompt' => $q['prompt'], 'options' => $q['options'], 'correct_option_index' => $q['correct']]);
        }
    }

    private function createFrenchVocabQuiz(User $user): void
    {
        $quiz = Quiz::create([
            'ulid' => strtolower(Str::ulid()),
            'user_id' => $user->id,
            'title' => 'French Vocabulary Basics',
            'description' => 'Common French words and phrases every learner should know.',
            'mode' => 'casual',
            'seconds_per_question' => 7,
            'status' => 'published',
            'is_listed' => true,
            'published_at' => now()->subDays(14),
            'runs_count' => 65,
            'passes_count' => 25,
            'reached_sum' => 420,
        ]);

        $questions = [
            ['prompt' => 'What does "Bonjour" mean?', 'options' => ['Goodbye', 'Please', 'Thank you', 'Hello / Good day'], 'correct' => 3],
            ['prompt' => 'What does "Merci" mean?', 'options' => ['Sorry', 'Thank you', 'Please', 'Yes'], 'correct' => 1],
            ['prompt' => 'What does "Maison" mean?', 'options' => ['School', 'Car', 'House', 'Garden'], 'correct' => 2],
            ['prompt' => 'What does "Chien" mean?', 'options' => ['Cat', 'Bird', 'Fish', 'Dog'], 'correct' => 3],
            ['prompt' => 'What does "Manger" mean?', 'options' => ['To sleep', 'To eat', 'To run', 'To drink'], 'correct' => 1],
            ['prompt' => 'What does "Beau / Belle" mean?', 'options' => ['Old', 'Ugly', 'Beautiful', 'Strange'], 'correct' => 2],
            ['prompt' => 'What does "Vouloir" mean?', 'options' => ['To see', 'To know', 'To be able to', 'To want'], 'correct' => 3],
            ['prompt' => 'What does "Aujourd\'hui" mean?', 'options' => ['Yesterday', 'Tomorrow', 'Today', 'Now'], 'correct' => 2],
        ];

        foreach ($questions as $i => $q) {
            $quiz->questions()->create(['position' => $i, 'prompt' => $q['prompt'], 'options' => $q['options'], 'correct_option_index' => $q['correct']]);
        }
    }

    private function createMedicalTermsQuiz(User $user): void
    {
        $quiz = Quiz::create([
            'ulid' => strtolower(Str::ulid()),
            'user_id' => $user->id,
            'title' => 'Medical Terminology',
            'description' => 'Essential medical terms used in healthcare settings.',
            'mode' => 'strict',
            'seconds_per_question' => 9,
            'status' => 'published',
            'is_listed' => true,
            'published_at' => now()->subDays(5),
            'runs_count' => 15,
            'passes_count' => 3,
            'reached_sum' => 80,
        ]);

        $questions = [
            ['prompt' => 'What does "Hypertension" mean?', 'options' => ['Low blood pressure', 'Normal blood pressure', 'High blood pressure', 'Irregular heartbeat'], 'correct' => 2],
            ['prompt' => 'What does "Myocardial" refer to?', 'options' => ['Related to the kidneys', 'Related to the lungs', 'Related to the brain', 'Related to the heart muscle'], 'correct' => 3],
            ['prompt' => 'What does "Dyspnea" mean?', 'options' => ['Difficulty swallowing', 'Difficulty breathing', 'Difficulty speaking', 'Difficulty walking'], 'correct' => 1],
            ['prompt' => 'What does "Edema" refer to?', 'options' => ['Tissue inflammation', 'Swelling from fluid accumulation', 'Internal bleeding', 'Nerve damage'], 'correct' => 1],
            ['prompt' => 'What does "Febrile" mean?', 'options' => ['Lacking energy', 'Related to breathing', 'Relating to fever', 'Cold to the touch'], 'correct' => 2],
            ['prompt' => 'What does "Acute" mean in medical context?', 'options' => ['Lasting a long time', 'Of short duration and sudden onset', 'Related to old age', 'Affecting multiple organs'], 'correct' => 1],
        ];

        foreach ($questions as $i => $q) {
            $quiz->questions()->create(['position' => $i, 'prompt' => $q['prompt'], 'options' => $q['options'], 'correct_option_index' => $q['correct']]);
        }
    }

    private function createBusinessVocabQuiz(User $user): void
    {
        $quiz = Quiz::create([
            'ulid' => strtolower(Str::ulid()),
            'user_id' => $user->id,
            'title' => 'Business English Vocabulary',
            'description' => 'Professional business terms for the modern workplace.',
            'mode' => 'casual',
            'seconds_per_question' => 8,
            'status' => 'published',
            'is_listed' => true,
            'published_at' => now()->subDays(3),
            'runs_count' => 20,
            'passes_count' => 9,
            'reached_sum' => 140,
        ]);

        $questions = [
            ['prompt' => 'What does "ROI" stand for?', 'options' => ['Rate of Income', 'Return on Investment', 'Revenue over Interest', 'Risk of Inflation'], 'correct' => 1],
            ['prompt' => 'What does "Synergy" mean in business?', 'options' => ['Cost reduction strategy', 'Combined effect greater than the sum of parts', 'A type of contract', 'Employee performance review'], 'correct' => 1],
            ['prompt' => 'What does "KPI" stand for?', 'options' => ['Key Performance Indicator', 'Known Process Index', 'Kinetic Profit Initiative', 'Key Project Item'], 'correct' => 0],
            ['prompt' => 'What does "Pivot" mean for a startup?', 'options' => ['Increase funding', 'Change core business strategy', 'Hire more employees', 'Enter a new geographic market'], 'correct' => 1],
            ['prompt' => 'What is "Market Saturation"?', 'options' => ['When supply exceeds demand', 'When a market has maximum competition', 'When all potential customers are already served', 'When prices are at their peak'], 'correct' => 2],
            ['prompt' => 'What does "B2B" mean?', 'options' => ['Back to Basics', 'Business to Business', 'Budget to Budget', 'Buyer to Broker'], 'correct' => 1],
            ['prompt' => 'What is "Churn Rate"?', 'options' => ['Rate of new customer acquisition', 'Rate of product production', 'Rate of profit growth', 'Rate of customer attrition'], 'correct' => 3],
        ];

        foreach ($questions as $i => $q) {
            $quiz->questions()->create(['position' => $i, 'prompt' => $q['prompt'], 'options' => $q['options'], 'correct_option_index' => $q['correct']]);
        }
    }
}
