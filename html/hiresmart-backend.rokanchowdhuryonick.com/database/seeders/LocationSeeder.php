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

        // Check if Bangladesh already exists
        $bangladesh = Country::firstOrCreate(['name' => 'Bangladesh']);

        // Create divisions (states) - Bulk Insert
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

        // Get existing divisions to avoid duplicates
        $existingDivisions = State::where('country_id', $bangladesh->id)
            ->pluck('name')->toArray();
        
        $newDivisions = array_diff($divisions, $existingDivisions);
        
        if (!empty($newDivisions)) {
            $divisionData = array_map(fn($name) => [
                'country_id' => $bangladesh->id,
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now()
            ], $newDivisions);
            
            State::insert($divisionData);
        }

        // Seed cities and areas with bulk operations
        $this->seedCitiesAndAreasBulk($bangladesh);

        $this->command->info('✅ Bangladesh location data seeded successfully!');
    }

    private function seedCitiesAndAreasBulk(Country $country): void
    {
        // Get all divisions with their data
        $divisions = State::where('country_id', $country->id)->get();
        
        $allCityData = [];
        $allAreaData = [];
        
        foreach ($divisions as $division) {
            $cityData = $this->getCityData($division->name);
            
            foreach ($cityData as $cityName => $areas) {
                // Prepare city data for bulk insert
                $allCityData[] = [
                    'state_id' => $division->id,
                    'name' => $cityName,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }
        
        // Bulk insert cities (avoid duplicates)
        $existingCities = City::whereIn('state_id', $divisions->pluck('id'))
            ->pluck('name', 'state_id')->toArray();
            
        $newCityData = array_filter($allCityData, function($city) use ($existingCities) {
            return !isset($existingCities[$city['state_id']]) || 
                   !in_array($city['name'], (array)$existingCities[$city['state_id']]);
        });
        
        if (!empty($newCityData)) {
            City::insert($newCityData);
        }
        
        // Now get all cities with their IDs for area creation
        $cities = City::whereIn('state_id', $divisions->pluck('id'))->get();
        
        foreach ($divisions as $division) {
            $cityData = $this->getCityData($division->name);
            
            foreach ($cityData as $cityName => $areas) {
                $city = $cities->where('state_id', $division->id)
                              ->where('name', $cityName)->first();
                
                if ($city) {
                    foreach ($areas as $areaName) {
                        $allAreaData[] = [
                            'country_id' => $country->id,
                            'state_id' => $division->id,
                            'city_id' => $city->id,
                            'name' => $areaName,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }
        }
        
        // Bulk insert areas (avoid duplicates)
        $existingAreas = Area::whereIn('city_id', $cities->pluck('id'))
            ->pluck('name', 'city_id')->toArray();
            
        $newAreaData = array_filter($allAreaData, function($area) use ($existingAreas) {
            return !isset($existingAreas[$area['city_id']]) || 
                   !in_array($area['name'], (array)$existingAreas[$area['city_id']]);
        });
        
        if (!empty($newAreaData)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($newAreaData, 100);
            foreach ($chunks as $chunk) {
                Area::insert($chunk);
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