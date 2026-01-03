<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnWidths
{
    protected $users;

    /**
     * Constructor to accept filtered users
     */
    public function __construct($users = null)
    {
        $this->users = $users;
    }

    /**
     * Return the collection of users
     */
    public function collection()
    {
        // If users are passed (filtered), use them with employee relationship
        if ($this->users) {
            return $this->users;
        }

        // Otherwise get all users with employee relationship
        return User::with('referredByEmployee')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Define the headings for the Excel sheet
     */
    public function headings(): array
    {
        return [
            'ID',
            'Contact Number',
            'Name',
            'Email',
            'Referral Emp ID',          // ✅ NEW
            'Referred By Employee',     // ✅ NEW
            'DOB',
            'Gender',
            'Emergency Contact',
            'Aadhaar Number',
            'PAN Number',
            'Full Address',
            'State',
            'City',
            'Pincode',
            'Country',
            'Bank Name',
            'Account Number',
            'IFSC',
            'Postal Code',
            'Is Verified',
            'Is Completed',
            'Aadhaar Verified',
            'PAN Verified',
            'RC Verified',
            'Same Address',
            'Declaration',
            'App Installed At',         // ✅ NEW
            'Created At',
            'Updated At',
            'Last Login At',
            'Login Count',
            'OTP Attempts',
            'OTP Resend Count',
            'Last OTP Sent At',
            'Verification Completed At'
        ];
    }

    /**
     * Map each user to an array for export
     */
    public function map($user): array
    {
        return [
            $user->id,
            $user->contact_number,
            $user->name ?? 'N/A',
            $user->email ?? 'N/A',
            $user->referral_emp_id ?? 'N/A',                                                    // ✅ NEW
            $user->referredByEmployee ? $user->referredByEmployee->name : 'No Referral',      // ✅ NEW
            $user->dob ?? 'N/A',
            ucfirst($user->gender ?? 'N/A'),
            $user->emergency_contact ?? 'N/A',
            $user->aadhar_number ?? 'N/A',
            $user->pan_number ?? 'N/A',
            $user->full_address ?? 'N/A',
            $user->state ?? 'N/A',
            $user->city ?? 'N/A',
            $user->pincode ?? 'N/A',
            $user->country ?? 'N/A',
            $user->bank_name ?? 'N/A',
            $user->account_number ?? 'N/A',
            $user->ifsc ?? 'N/A',
            $user->postal_code ?? 'N/A',
            $user->is_verified ? 'Yes' : 'No',
            $user->is_completed ? 'Yes' : 'No',
            $user->aadhaar_verified ? 'Yes' : 'No',
            $user->pan_verified ? 'Yes' : 'No',
            $user->rc_verified ? 'Yes' : 'No',
            $user->same_address ? 'Yes' : 'No',
            $user->declaration ? 'Yes' : 'No',
            $user->app_installed_at ? $user->app_installed_at->format('d-m-Y H:i:s') : 'N/A',  // ✅ NEW
            $user->created_at ? $user->created_at->format('d-m-Y H:i:s') : 'N/A',
            $user->updated_at ? $user->updated_at->format('d-m-Y H:i:s') : 'N/A',
            $user->last_login_at ? $user->last_login_at->format('d-m-Y H:i:s') : 'Never',
            $user->login_count ?? 0,
            $user->otp_attempts ?? 0,
            $user->otp_resend_count ?? 0,
            $user->last_otp_sent_at ? $user->last_otp_sent_at->format('d-m-Y H:i:s') : 'N/A',
            $user->verification_completed_at ? $user->verification_completed_at->format('d-m-Y H:i:s') : 'N/A'
        ];
    }

    /**
     * Define styles for the Excel sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style for heading row (row 1)
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
            // Style for all other rows
            'A2:AJ' . ($sheet->getHighestRow()) => [
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

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 15,  // Contact Number
            'C' => 20,  // Name
            'D' => 25,  // Email
            'E' => 15,  // Referral Emp ID ✅ NEW
            'F' => 20,  // Referred By Employee ✅ NEW
            'G' => 12,  // DOB
            'H' => 10,  // Gender
            'I' => 15,  // Emergency Contact
            'J' => 18,  // Aadhaar Number
            'K' => 15,  // PAN Number
            'L' => 35,  // Full Address
            'M' => 15,  // State
            'N' => 15,  // City
            'O' => 10,  // Pincode
            'P' => 12,  // Country
            'Q' => 20,  // Bank Name
            'R' => 18,  // Account Number
            'S' => 12,  // IFSC
            'T' => 10,  // Postal Code
            'U' => 12,  // Is Verified
            'V' => 12,  // Is Completed
            'W' => 15,  // Aadhaar Verified
            'X' => 12,  // PAN Verified
            'Y' => 12,  // RC Verified
            'Z' => 12,  // Same Address
            'AA' => 12, // Declaration
            'AB' => 18, // App Installed At ✅ NEW
            'AC' => 18, // Created At
            'AD' => 18, // Updated At
            'AE' => 18, // Last Login At
            'AF' => 12, // Login Count
            'AG' => 12, // OTP Attempts
            'AH' => 15, // OTP Resend Count
            'AI' => 18, // Last OTP Sent At
            'AJ' => 22, // Verification Completed At
        ];
    }
}
