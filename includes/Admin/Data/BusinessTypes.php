<?php
/**
 * Business Types data class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Data;

/**
 * Contains LocalBusiness Schema.org types.
 */
final class BusinessTypes {

	/**
	 * Get all business types.
	 *
	 * @return array<string, string>
	 */
	public static function get_all(): array {
		return [
			'LocalBusiness'               => __( 'Local Business (General)', 'lw-seo' ),
			'AnimalShelter'               => __( 'Animal Shelter', 'lw-seo' ),
			'AutomotiveBusiness'          => __( 'Automotive Business', 'lw-seo' ),
			'AutoBodyShop'                => __( 'Auto Body Shop', 'lw-seo' ),
			'AutoDealer'                  => __( 'Auto Dealer', 'lw-seo' ),
			'AutoPartsStore'              => __( 'Auto Parts Store', 'lw-seo' ),
			'AutoRental'                  => __( 'Auto Rental', 'lw-seo' ),
			'AutoRepair'                  => __( 'Auto Repair', 'lw-seo' ),
			'AutoWash'                    => __( 'Auto Wash', 'lw-seo' ),
			'GasStation'                  => __( 'Gas Station', 'lw-seo' ),
			'MotorcycleDealer'            => __( 'Motorcycle Dealer', 'lw-seo' ),
			'MotorcycleRepair'            => __( 'Motorcycle Repair', 'lw-seo' ),
			'ChildCare'                   => __( 'Child Care', 'lw-seo' ),
			'Dentist'                     => __( 'Dentist', 'lw-seo' ),
			'DryCleaningOrLaundry'        => __( 'Dry Cleaning / Laundry', 'lw-seo' ),
			'EmergencyService'            => __( 'Emergency Service', 'lw-seo' ),
			'EmploymentAgency'            => __( 'Employment Agency', 'lw-seo' ),
			'EntertainmentBusiness'       => __( 'Entertainment Business', 'lw-seo' ),
			'AmusementPark'               => __( 'Amusement Park', 'lw-seo' ),
			'ArtGallery'                  => __( 'Art Gallery', 'lw-seo' ),
			'Casino'                      => __( 'Casino', 'lw-seo' ),
			'ComedyClub'                  => __( 'Comedy Club', 'lw-seo' ),
			'NightClub'                   => __( 'Night Club', 'lw-seo' ),
			'FinancialService'            => __( 'Financial Service', 'lw-seo' ),
			'AccountingService'           => __( 'Accounting Service', 'lw-seo' ),
			'Bank'                        => __( 'Bank', 'lw-seo' ),
			'InsuranceAgency'             => __( 'Insurance Agency', 'lw-seo' ),
			'FoodEstablishment'           => __( 'Food Establishment', 'lw-seo' ),
			'Bakery'                      => __( 'Bakery', 'lw-seo' ),
			'BarOrPub'                    => __( 'Bar / Pub', 'lw-seo' ),
			'Brewery'                     => __( 'Brewery', 'lw-seo' ),
			'CafeOrCoffeeShop'            => __( 'Cafe / Coffee Shop', 'lw-seo' ),
			'FastFoodRestaurant'          => __( 'Fast Food Restaurant', 'lw-seo' ),
			'IceCreamShop'                => __( 'Ice Cream Shop', 'lw-seo' ),
			'Restaurant'                  => __( 'Restaurant', 'lw-seo' ),
			'Winery'                      => __( 'Winery', 'lw-seo' ),
			'GovernmentOffice'            => __( 'Government Office', 'lw-seo' ),
			'HealthAndBeautyBusiness'     => __( 'Health & Beauty Business', 'lw-seo' ),
			'BeautySalon'                 => __( 'Beauty Salon', 'lw-seo' ),
			'DaySpa'                      => __( 'Day Spa', 'lw-seo' ),
			'HairSalon'                   => __( 'Hair Salon', 'lw-seo' ),
			'NailSalon'                   => __( 'Nail Salon', 'lw-seo' ),
			'TattooParlor'                => __( 'Tattoo Parlor', 'lw-seo' ),
			'HomeAndConstructionBusiness' => __( 'Home & Construction Business', 'lw-seo' ),
			'Electrician'                 => __( 'Electrician', 'lw-seo' ),
			'HVACBusiness'                => __( 'HVAC Business', 'lw-seo' ),
			'Locksmith'                   => __( 'Locksmith', 'lw-seo' ),
			'Plumber'                     => __( 'Plumber', 'lw-seo' ),
			'RoofingContractor'           => __( 'Roofing Contractor', 'lw-seo' ),
			'InternetCafe'                => __( 'Internet Cafe', 'lw-seo' ),
			'LegalService'                => __( 'Legal Service', 'lw-seo' ),
			'Attorney'                    => __( 'Attorney', 'lw-seo' ),
			'Notary'                      => __( 'Notary', 'lw-seo' ),
			'Library'                     => __( 'Library', 'lw-seo' ),
			'LodgingBusiness'             => __( 'Lodging Business', 'lw-seo' ),
			'Hotel'                       => __( 'Hotel', 'lw-seo' ),
			'Motel'                       => __( 'Motel', 'lw-seo' ),
			'Hostel'                      => __( 'Hostel', 'lw-seo' ),
			'MedicalBusiness'             => __( 'Medical Business', 'lw-seo' ),
			'Optician'                    => __( 'Optician', 'lw-seo' ),
			'Pharmacy'                    => __( 'Pharmacy', 'lw-seo' ),
			'Physician'                   => __( 'Physician', 'lw-seo' ),
			'ProfessionalService'         => __( 'Professional Service', 'lw-seo' ),
			'RadioStation'                => __( 'Radio Station', 'lw-seo' ),
			'RealEstateAgent'             => __( 'Real Estate Agent', 'lw-seo' ),
			'RecyclingCenter'             => __( 'Recycling Center', 'lw-seo' ),
			'SelfStorage'                 => __( 'Self Storage', 'lw-seo' ),
			'ShoppingCenter'              => __( 'Shopping Center', 'lw-seo' ),
			'SportsActivityLocation'      => __( 'Sports Activity Location', 'lw-seo' ),
			'GolfCourse'                  => __( 'Golf Course', 'lw-seo' ),
			'HealthClub'                  => __( 'Health Club / Gym', 'lw-seo' ),
			'SportsClub'                  => __( 'Sports Club', 'lw-seo' ),
			'StadiumOrArena'              => __( 'Stadium / Arena', 'lw-seo' ),
			'Store'                       => __( 'Store (General)', 'lw-seo' ),
			'BikeStore'                   => __( 'Bike Store', 'lw-seo' ),
			'BookStore'                   => __( 'Book Store', 'lw-seo' ),
			'ClothingStore'               => __( 'Clothing Store', 'lw-seo' ),
			'ComputerStore'               => __( 'Computer Store', 'lw-seo' ),
			'ConvenienceStore'            => __( 'Convenience Store', 'lw-seo' ),
			'ElectronicsStore'            => __( 'Electronics Store', 'lw-seo' ),
			'Florist'                     => __( 'Florist', 'lw-seo' ),
			'FurnitureStore'              => __( 'Furniture Store', 'lw-seo' ),
			'GardenStore'                 => __( 'Garden Store', 'lw-seo' ),
			'GroceryStore'                => __( 'Grocery Store', 'lw-seo' ),
			'HardwareStore'               => __( 'Hardware Store', 'lw-seo' ),
			'HobbyShop'                   => __( 'Hobby Shop', 'lw-seo' ),
			'JewelryStore'                => __( 'Jewelry Store', 'lw-seo' ),
			'LiquorStore'                 => __( 'Liquor Store', 'lw-seo' ),
			'MobilePhoneStore'            => __( 'Mobile Phone Store', 'lw-seo' ),
			'MovieRentalStore'            => __( 'Movie Rental Store', 'lw-seo' ),
			'MusicStore'                  => __( 'Music Store', 'lw-seo' ),
			'OfficeEquipmentStore'        => __( 'Office Equipment Store', 'lw-seo' ),
			'OutletStore'                 => __( 'Outlet Store', 'lw-seo' ),
			'PawnShop'                    => __( 'Pawn Shop', 'lw-seo' ),
			'PetStore'                    => __( 'Pet Store', 'lw-seo' ),
			'ShoeStore'                   => __( 'Shoe Store', 'lw-seo' ),
			'SportingGoodsStore'          => __( 'Sporting Goods Store', 'lw-seo' ),
			'TireShop'                    => __( 'Tire Shop', 'lw-seo' ),
			'ToyStore'                    => __( 'Toy Store', 'lw-seo' ),
			'WholesaleStore'              => __( 'Wholesale Store', 'lw-seo' ),
			'TelevisionStation'           => __( 'Television Station', 'lw-seo' ),
			'TouristInformationCenter'    => __( 'Tourist Information Center', 'lw-seo' ),
			'TravelAgency'                => __( 'Travel Agency', 'lw-seo' ),
		];
	}

	/**
	 * Get grouped business types for select field.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_grouped(): array {
		return [
			__( 'General', 'lw-seo' )         => [
				'LocalBusiness'       => __( 'Local Business', 'lw-seo' ),
				'ProfessionalService' => __( 'Professional Service', 'lw-seo' ),
			],
			__( 'Automotive', 'lw-seo' )      => [
				'AutoBodyShop'   => __( 'Auto Body Shop', 'lw-seo' ),
				'AutoDealer'     => __( 'Auto Dealer', 'lw-seo' ),
				'AutoPartsStore' => __( 'Auto Parts Store', 'lw-seo' ),
				'AutoRepair'     => __( 'Auto Repair', 'lw-seo' ),
				'AutoWash'       => __( 'Auto Wash', 'lw-seo' ),
				'GasStation'     => __( 'Gas Station', 'lw-seo' ),
			],
			__( 'Food & Drink', 'lw-seo' )    => [
				'Restaurant'         => __( 'Restaurant', 'lw-seo' ),
				'CafeOrCoffeeShop'   => __( 'Cafe / Coffee Shop', 'lw-seo' ),
				'Bakery'             => __( 'Bakery', 'lw-seo' ),
				'BarOrPub'           => __( 'Bar / Pub', 'lw-seo' ),
				'FastFoodRestaurant' => __( 'Fast Food Restaurant', 'lw-seo' ),
			],
			__( 'Health & Beauty', 'lw-seo' ) => [
				'BeautySalon' => __( 'Beauty Salon', 'lw-seo' ),
				'HairSalon'   => __( 'Hair Salon', 'lw-seo' ),
				'DaySpa'      => __( 'Day Spa', 'lw-seo' ),
				'Dentist'     => __( 'Dentist', 'lw-seo' ),
				'Physician'   => __( 'Physician', 'lw-seo' ),
				'Pharmacy'    => __( 'Pharmacy', 'lw-seo' ),
			],
			__( 'Store', 'lw-seo' )           => [
				'Store'            => __( 'Store (General)', 'lw-seo' ),
				'ClothingStore'    => __( 'Clothing Store', 'lw-seo' ),
				'ElectronicsStore' => __( 'Electronics Store', 'lw-seo' ),
				'GroceryStore'     => __( 'Grocery Store', 'lw-seo' ),
				'HardwareStore'    => __( 'Hardware Store', 'lw-seo' ),
				'JewelryStore'     => __( 'Jewelry Store', 'lw-seo' ),
			],
			__( 'Services', 'lw-seo' )        => [
				'Attorney'          => __( 'Attorney', 'lw-seo' ),
				'AccountingService' => __( 'Accounting Service', 'lw-seo' ),
				'RealEstateAgent'   => __( 'Real Estate Agent', 'lw-seo' ),
				'Electrician'       => __( 'Electrician', 'lw-seo' ),
				'Plumber'           => __( 'Plumber', 'lw-seo' ),
				'Locksmith'         => __( 'Locksmith', 'lw-seo' ),
			],
			__( 'Lodging', 'lw-seo' )         => [
				'Hotel'  => __( 'Hotel', 'lw-seo' ),
				'Motel'  => __( 'Motel', 'lw-seo' ),
				'Hostel' => __( 'Hostel', 'lw-seo' ),
			],
		];
	}
}
