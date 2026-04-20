<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiseaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $diseases = [
            [
                'name' => 'Bacterial Blight',
                'note' => 'A serious bacterial disease that starts as yellowing at the leaf tips and edges, later turning brown and dry. It can spread quickly through water and wind, reducing yield.',
                'solutions' => "• Grow resistant rice varieties to minimize the risk of infection in future crops\n• Avoid overuse of nitrogen fertilizer, as it makes plants more vulnerable to bacteria\n• Maintain proper drainage and avoid stagnant water, which helps bacteria spread\n• Remove and destroy infected plants or leaves to prevent transmission to healthy plants\n• In severe cases, apply recommended bactericides under proper guidance",
            ],
            [
                'name' => 'Brown Spot',
                'note' => 'A common fungal disease that appears as small brown or dark spots on leaves. Severe infection can cause leaf drying and poor grain quality.',
                'solutions' => "• Use high-quality, disease-free seeds and treat seeds before planting to kill fungi\n• Apply balanced fertilizers, especially potassium, to improve plant resistance\n• Maintain consistent water supply and avoid drought stress, which increases infection\n• Remove heavily infected leaves to reduce fungal spread\n• Apply suitable fungicides if the disease spreads widely across the field",
            ],
            [
                'name' => 'Sheath Blight',
                'note' => 'A fungal disease that causes oval or irregular lesions on leaf sheaths near the water level. It spreads rapidly in warm, humid, and densely planted conditions.',
                'solutions' => "• Maintain proper spacing between plants to improve airflow and reduce humidity\n• Avoid excessive nitrogen fertilizer, which promotes dense growth and disease spread\n• Keep the field clean by removing weeds and infected plant debris\n• Manage water levels properly to avoid excessive moisture\n• Apply recommended fungicides when symptoms begin to spread quickly",
            ],
        ];

        foreach ($diseases as $diseaseData) {
            \App\Models\Disease::updateOrCreate(['name' => $diseaseData['name']], $diseaseData);
        }
    }
}
