<?php

namespace App\Exports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class VendorsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnWidths
{
    protected $vendors;

    public function __construct($vendors)
    {
        $this->vendors = $vendors;
    }

    public function collection()
    {
        // If collection passed, use it with relationships
        if ($this->vendors) {
            return $this->vendors;
        }

        // Otherwise get all with relationships
        return Vendor::with(['vehicleCategory', 'vehicleModel', 'referredByEmployee'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Referral Emp ID',              // ✅ NEW
            'Referred By Employee',         // ✅ NEW
            'App Installed At',             // ✅ NEW
            'Name',
            'Contact Number',
            'Email',
            'DOB',
            'Gender',
            'Emergency Contact',

            // Address
            'Full Address',
            'City',
            'State',
            'Pincode',
            'Postal Code',
            'Country',

            // Vehicle Category & Model
            'Vehicle Category',
            'Vehicle Model',
            'Vehicle Type Description',

            // Documents
            'Aadhaar Number',
            'Aadhaar Front',
            'Aadhaar Back',
            'PAN Number',
            'PAN Image',

            // RC Details
            'RC Number',
            'RC Image',
            'RC Verified',
            'RC Verification Date',

            // DL Details
            'DL Number',
            'DL Image',
            'DL Verified',
            'DL Verification Date',

            // Bank Details
            'Bank Name',
            'Account Number',
            'IFSC Code',

            // Vehicle Details
            'Vehicle Registration Number',
            'Vehicle Type',
            'Vehicle Brand Model',
            'Vehicle Length',
            'Vehicle Length Unit',
            'Vehicle Tyre Count',
            'Weight Capacity',
            'Weight Unit',
            'Vehicle Listed',
            'Vehicle Status',

            // Availability
            'Availability Status',
            'Last In Time',
            'Last Out Time',
            'Current Location',
            'Is Available for Booking',

            // Status
            'Is Verified',
            'Is Completed',
            'Declaration',

            // Activity
            'Login Count',
            'Last Login At',
            'Last Logout At',

            // Dates
            'Registration Date',
            'Last Updated',
        ];
    }

    public function map($vendor): array
    {
        return [
            $vendor->id,
            $vendor->referral_emp_id ?? 'N/A',                                                      // ✅ NEW
            $vendor->referredByEmployee ? $vendor->referredByEmployee->name : 'No Referral',       // ✅ NEW
            $vendor->app_installed_at ? $vendor->app_installed_at->format('d-m-Y H:i:s') : 'N/A', // ✅ NEW
            $vendor->name ?? 'N/A',
            $vendor->contact_number,
            $vendor->email ?? 'N/A',
            $vendor->dob ? $vendor->dob->format('d-m-Y') : 'N/A',
            ucfirst($vendor->gender ?? 'N/A'),
            $vendor->emergency_contact ?? 'N/A',

            // Address
            $vendor->full_address ?? 'N/A',
            $vendor->city ?? 'N/A',
            $vendor->state ?? 'N/A',
            $vendor->pincode ?? 'N/A',
            $vendor->postal_code ?? 'N/A',
            $vendor->country ?? 'N/A',

            // Vehicle Category & Model
            $vendor->vehicleCategory->category_name ?? 'N/A',
            $vendor->vehicleModel->model_name ?? 'N/A',
            $vendor->vehicleModel->vehicle_type_desc ?? 'N/A',

            // Documents
            $vendor->aadhar_number ?? 'N/A',
            $vendor->aadhar_front ?? 'Not Uploaded',
            $vendor->aadhar_back ?? 'Not Uploaded',
            $vendor->pan_number ?? 'N/A',
            $vendor->pan_image ?? 'Not Uploaded',

            // RC Details
            $vendor->rc_number ?? 'N/A',
            $vendor->rc_image ?? 'Not Uploaded',
            $vendor->rc_verified ? 'Yes' : 'No',
            $vendor->rc_verification_date ? $vendor->rc_verification_date->format('d-m-Y H:i:s') : 'N/A',

            // DL Details
            $vendor->dl_number ?? 'N/A',
            $vendor->dl_image ?? 'Not Uploaded',
            $vendor->dl_verified ? 'Yes' : 'No',
            $vendor->dl_verification_date ? $vendor->dl_verification_date->format('d-m-Y H:i:s') : 'N/A',

            // Bank Details
            $vendor->bank_name ?? 'N/A',
            $vendor->account_number ?? 'N/A',
            $vendor->ifsc ?? 'N/A',

            // Vehicle Details
            $vendor->vehicle_registration_number ?? 'N/A',
            $vendor->vehicle_type ?? 'N/A',
            $vendor->vehicle_brand_model ?? 'N/A',
            $vendor->vehicle_length ?? 'N/A',
            $vendor->vehicle_length_unit ?? 'N/A',
            $vendor->vehicle_tyre_count ?? 'N/A',
            $vendor->weight_capacity ?? 'N/A',
            $vendor->weight_unit ?? 'N/A',
            $vendor->vehicle_listed ? 'Yes' : 'No',
            ucfirst($vendor->vehicle_status ?? 'N/A'),

            // Availability
            ucfirst($vendor->availability_status ?? 'N/A'),
            $vendor->last_in_time ? $vendor->last_in_time->format('d-m-Y H:i:s') : 'N/A',
            $vendor->last_out_time ? $vendor->last_out_time->format('d-m-Y H:i:s') : 'N/A',
            $vendor->current_location ?? 'N/A',
            $vendor->is_available_for_booking ? 'Yes' : 'No',

            // Status
            $vendor->is_verified ? 'Verified' : 'Pending',
            $vendor->is_completed ? 'Yes' : 'No',
            $vendor->declaration ? 'Yes' : 'No',

            // Activity
            $vendor->login_count ?? 0,
            $vendor->last_login_at ? $vendor->last_login_at->format('d-m-Y H:i:s') : 'Never',
            $vendor->last_logout_at ? $vendor->last_logout_at->format('d-m-Y H:i:s') : 'N/A',

            // Dates
            $vendor->created_at->format('d-m-Y H:i:s'),
            $vendor->updated_at->format('d-m-Y H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ],

            // All data rows
            'A2:BN' . ($sheet->getHighestRow()) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 15,  // Referral Emp ID ✅
            'C' => 20,  // Referred By Employee ✅
            'D' => 18,  // App Installed At ✅
            'E' => 20,  // Name
            'F' => 15,  // Contact Number
            'G' => 25,  // Email
            'H' => 12,  // DOB
            'I' => 10,  // Gender
            'J' => 15,  // Emergency Contact
            'K' => 35,  // Full Address
            'L' => 15,  // City
            'M' => 15,  // State
            'N' => 10,  // Pincode
            'O' => 10,  // Postal Code
            'P' => 12,  // Country
            'Q' => 18,  // Vehicle Category
            'R' => 25,  // Vehicle Model
            'S' => 30,  // Vehicle Type Desc
            'T' => 18,  // Aadhaar Number
            'U' => 20,  // Aadhaar Front
            'V' => 20,  // Aadhaar Back
            'W' => 15,  // PAN Number
            'X' => 20,  // PAN Image
            'Y' => 18,  // RC Number
            'Z' => 20,  // RC Image
            'AA' => 12, // RC Verified
            'AB' => 18, // RC Verification Date
            'AC' => 18, // DL Number
            'AD' => 20, // DL Image
            'AE' => 12, // DL Verified
            'AF' => 18, // DL Verification Date
            'AG' => 20, // Bank Name
            'AH' => 18, // Account Number
            'AI' => 12, // IFSC
            'AJ' => 20, // Vehicle Reg Number
            'AK' => 15, // Vehicle Type
            'AL' => 20, // Vehicle Brand Model
            'AM' => 12, // Vehicle Length
            'AN' => 10, // Length Unit
            'AO' => 12, // Tyre Count
            'AP' => 12, // Weight Capacity
            'AQ' => 10, // Weight Unit
            'AR' => 12, // Vehicle Listed
            'AS' => 15, // Vehicle Status
            'AT' => 15, // Availability Status
            'AU' => 18, // Last In Time
            'AV' => 18, // Last Out Time
            'AW' => 25, // Current Location
            'AX' => 18, // Is Available
            'AY' => 12, // Is Verified
            'AZ' => 12, // Is Completed
            'BA' => 12, // Declaration
            'BB' => 12, // Login Count
            'BC' => 18, // Last Login At
            'BD' => 18, // Last Logout At
            'BE' => 18, // Registration Date
            'BF' => 18, // Last Updated
        ];
    }
}
