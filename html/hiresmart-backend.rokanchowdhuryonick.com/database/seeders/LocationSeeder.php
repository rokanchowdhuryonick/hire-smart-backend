<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Bangladesh location data...');

        // Create Bangladesh
        $bangladesh = Country::create([
            'name' => 'Bangladesh'
        ]);

        // Create divisions (states)
        $divisions = [
            'Dhaka Division',
            'Chittagong Division', 
            'Sylhet Division',
            'Rajshahi Division',
            'Rangpur Division',
            'Barisal Division',
            'Khulna Division',
            'Mymensingh Division'
        ];

        foreach ($divisions as $divisionName) {
            $division = State::create([
                'country_id' => $bangladesh->id,
                'name' => $divisionName
            ]);

            // Seed cities and areas based on division
            $this->seedCitiesAndAreas($bangladesh, $division);
        }

        $this->command->info('✅ Bangladesh location data seeded successfully!');
    }

    private function seedCitiesAndAreas(Country $country, State $division): void
    {
        $cityData = $this->getCityData($division->name);

        foreach ($cityData as $cityName => $areas) {
            $city = City::create([
                'state_id' => $division->id,
                'name' => $cityName
            ]);

            // Create areas for this city
            foreach ($areas as $areaName) {
                Area::create([
                    'country_id' => $country->id,
                    'state_id' => $division->id,
                    'city_id' => $city->id,
                    'name' => $areaName
                ]);
            }
        }
    }

    private function getCityData(string $divisionName): array
    {
        return match ($divisionName) {
            'Dhaka Division' => [
                'Dhaka' => [
                    // Central Dhaka
                    'Dhanmondi', 'Gulshan', 'Banani', 'Uttara', 'Motijheel', 
                    'Tejgaon', 'Ramna', 'Wari', 'Old Dhaka', 'New Market',
                    'Azimpur', 'Shahbagh', 'TSC', 'Elephant Road', 'Green Road',
                    
                    // North Dhaka
                    'Mohakhali', 'Farmgate', 'Kawran Bazar', 'Karwan Bazar',
                    'Panthapath', 'Kalabagan', 'Bashundhara', 'Baridhara',
                    'Nikunja', 'Khilkhet', 'Airport', 'Cantonment', 'Banani DOHS',
                    
                    // West Dhaka  
                    'Mirpur', 'Mirpur 1', 'Mirpur 2', 'Mirpur 10', 'Mirpur 11',
                    'Mirpur 12', 'Pallabi', 'Kafrul', 'Agargaon', 'Sher-e-Bangla Nagar',
                    'Darus Salam', 'Mirpur DOHS', 'Kazipara',
                    
                    // South Dhaka
                    'Lalmatia', 'Mohammadpur', 'Shyamoli', 'Adabar', 'Hazaribagh',
                    'Lalbagh', 'Chawkbazar', 'Islampur', 'Sadarghat', 'Kamrangirchar',
                    'Donia', 'Rayerbagh', 'Mugda', 'Malibagh', 'Mouchak',
                    
                    // East Dhaka
                    'Badda', 'Rampura', 'Banasree', 'Meradia', 'Khilgaon',
                    'Tilpapara', 'Segunbagicha', 'Eskaton', 'Moghbazar',
                    'Hatirjheel', 'Gulshan 1', 'Gulshan 2', 'Baridhara DOHS'
                ],
                'Gazipur' => ['Gazipur Sadar', 'Kaliakair', 'Kapasia'],
                'Narayanganj' => ['Narayanganj Sadar', 'Sonargaon', 'Araihazar'],
                'Manikganj' => ['Manikganj Sadar', 'Ghior', 'Shibalaya']
            ],
            'Chittagong Division' => [
                'Chittagong' => [
                    'Agrabad', 'Nasirabad', 'Panchlaish', 'Khulshi', 
                    'Halishahar', 'Chandgaon', 'Bayazid', 'GEC'
                ],
                'Cox\'s Bazar' => ['Cox\'s Bazar Sadar', 'Teknaf', 'Ukhia'],
                'Comilla' => ['Comilla Sadar', 'Chandina', 'Burichang']
            ],
            'Sylhet Division' => [
                'Sylhet' => [
                    'Sylhet Sadar', 'Zindabazar', 'Ambarkhana', 'Bagbari',
                    'Chowhatta', 'Subhanighat', 'Mira Bazar'
                ],
                'Moulvibazar' => ['Moulvibazar Sadar', 'Srimangal', 'Kamalganj'],
                'Habiganj' => ['Habiganj Sadar', 'Madhabpur', 'Bahubal']
            ],
            'Rajshahi Division' => [
                'Rajshahi' => [
                    'Rajshahi Sadar', 'Boalia', 'Motihar', 'Shah Makhdum', 
                    'Rajpara', 'Kazla', 'Talaimari'
                ],
                'Bogra' => ['Bogra Sadar', 'Sherpur', 'Shajahanpur'],
                'Pabna' => ['Pabna Sadar', 'Ishwardi', 'Atgharia']
            ],
            'Khulna Division' => [
                'Khulna' => [
                    'Khulna Sadar', 'Daulatpur', 'Khalishpur', 'Sonadanga',
                    'Khan Jahan Ali', 'Harintana', 'Boyra'
                ],
                'Jessore' => ['Jessore Sadar', 'Chaugachha', 'Jhikargachha'],
                'Satkhira' => ['Satkhira Sadar', 'Kaliganj', 'Shyamnagar']
            ],
            default => [
                $divisionName === 'Rangpur Division' ? 'Rangpur' : 
                ($divisionName === 'Barisal Division' ? 'Barisal' : 'Mymensingh') => [
                    $divisionName === 'Rangpur Division' ? 'Rangpur Sadar' : 
                    ($divisionName === 'Barisal Division' ? 'Barisal Sadar' : 'Mymensingh Sadar')
                ]
            ]
        };
    }
} 