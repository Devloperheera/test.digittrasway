<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class UserDetailExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithColumnWidths
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Return the collection of user details
     */
    public function collection()
    {
        return new Collection([
            ['Field', 'Value'],
            ['', ''],
            ['PERSONAL INFORMATION', ''],
            ['ID', $this->user->id],
            ['Name', $this->user->name ?? 'N/A'],
            ['Contact Number', $this->user->contact_number],
            ['Email', $this->user->email ?? 'N/A'],
            ['Date of Birth', $this->user->dob ?? 'N/A'],
            ['Gender', ucfirst($this->user->gender ?? 'N/A')],
            ['Emergency Contact', $this->user->emergency_contact ?? 'N/A'],
            ['', ''],

            // ✅ NEW: EMPLOYEE REFERRAL INFORMATION
            ['EMPLOYEE REFERRAL INFORMATION', ''],
            ['Referral Employee ID', $this->user->referral_emp_id ?? 'No Referral'],
            ['Referred By Employee', $this->user->referredByEmployee ? $this->user->referredByEmployee->name : 'No Referral'],
            ['Employee Email', $this->user->referredByEmployee ? $this->user->referredByEmployee->email : 'N/A'],
            ['Employee Phone', $this->user->referredByEmployee ? $this->user->referredByEmployee->phone : 'N/A'],
            ['Employee Department', $this->user->referredByEmployee ? $this->user->referredByEmployee->department : 'N/A'],
            ['Employee Designation', $this->user->referredByEmployee ? $this->user->referredByEmployee->designation : 'N/A'],
            ['App Installed At', $this->user->app_installed_at ? $this->user->app_installed_at->format('d-m-Y H:i:s') : 'N/A'],
            ['', ''],

            ['DOCUMENT INFORMATION', ''],
            ['Aadhaar Number', $this->user->aadhar_number ?? 'N/A'],
            ['Aadhaar Verified', $this->user->aadhaar_verified ? 'Yes' : 'No'],
            ['Aadhaar Verification Date', $this->user->aadhaar_verification_date ? $this->user->aadhaar_verification_date->format('d-m-Y H:i:s') : 'N/A'],
            ['Aadhaar Front Image', $this->user->aadhar_front ?? 'Not Uploaded'],
            ['Aadhaar Back Image', $this->user->aadhar_back ?? 'Not Uploaded'],
            ['PAN Number', $this->user->pan_number ?? 'N/A'],
            ['PAN Verified', $this->user->pan_verified ? 'Yes' : 'No'],
            ['PAN Image', $this->user->pan_image ?? 'Not Uploaded'],
            ['RC Verified', $this->user->rc_verified ? 'Yes' : 'No'],
            ['Digilocker Client ID', $this->user->aadhaar_digilocker_client_id ?? 'N/A'],
            ['Aadhaar Verified Data', $this->user->aadhaar_verified_data ? 'Available' : 'Not Available'],
            ['', ''],

            ['ADDRESS INFORMATION', ''],
            ['Full Address', $this->user->full_address ?? 'N/A'],
            ['City', $this->user->city ?? 'N/A'],
            ['State', $this->user->state ?? 'N/A'],
            ['Pincode', $this->user->pincode ?? 'N/A'],
            ['Postal Code', $this->user->postal_code ?? 'N/A'],
            ['Country', $this->user->country ?? 'N/A'],
            ['Same Address', $this->user->same_address ? 'Yes' : 'No'],
            ['Verified Address', $this->user->verified_address ?? 'N/A'],
            ['Verified Pincode', $this->user->verified_pincode ?? 'N/A'],
            ['Verified State', $this->user->verified_state ?? 'N/A'],
            ['', ''],

            ['BANK INFORMATION', ''],
            ['Bank Name', $this->user->bank_name ?? 'N/A'],
            ['Account Number', $this->user->account_number ?? 'N/A'],
            ['IFSC Code', $this->user->ifsc ?? 'N/A'],
            ['', ''],

            ['ACCOUNT STATUS', ''],
            ['Is Verified', $this->user->is_verified ? 'Yes' : 'No'],
            ['Is Completed', $this->user->is_completed ? 'Yes' : 'No'],
            ['Declaration', $this->user->declaration ? 'Accepted' : 'Not Accepted'],
            ['Password Set', !empty($this->user->password) ? 'Yes' : 'No'],
            ['', ''],

            ['VERIFICATION DETAILS', ''],
            ['Verified DOB', $this->user->verified_dob ?? 'N/A'],
            ['Verified Gender', $this->user->verified_gender ?? 'N/A'],
            ['Verification Completed At', $this->user->verification_completed_at ? $this->user->verification_completed_at->format('d-m-Y H:i:s') : 'N/A'],
            ['', ''],

            ['OTP INFORMATION', ''],
            ['OTP', $this->user->otp ?? 'None'],
            ['OTP Attempts', $this->user->otp_attempts ?? 0],
            ['OTP Resend Count', $this->user->otp_resend_count ?? 0],
            ['Last OTP Sent At', $this->user->last_otp_sent_at ? $this->user->last_otp_sent_at->format('d-m-Y H:i:s') : 'N/A'],
            ['OTP Expires At', $this->user->otp_expires_at ? $this->user->otp_expires_at->format('d-m-Y H:i:s') : 'N/A'],
            ['', ''],

            ['ACTIVITY LOGS', ''],
            ['Login Count', $this->user->login_count ?? 0],
            ['Last Login At', $this->user->last_login_at ? $this->user->last_login_at->format('d-m-Y H:i:s') : 'Never'],
            ['Created At', $this->user->created_at ? $this->user->created_at->format('d-m-Y H:i:s') : 'N/A'],
            ['Updated At', $this->user->updated_at ? $this->user->updated_at->format('d-m-Y H:i:s') : 'N/A'],
            ['Remember Token', $this->user->remember_token ? 'Set' : 'Not Set'],
        ]);
    }

    /**
     * Define headings (empty as we have custom structure)
     */
    public function headings(): array
    {
        return [];
    }

    /**
     * Define styles for the Excel sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Header row (Field, Value)
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],

            // Section Headers styling
            3 => $this->getSectionHeaderStyle(),   // PERSONAL INFORMATION
            12 => $this->getSectionHeaderStyle(),  // ✅ EMPLOYEE REFERRAL INFORMATION
            21 => $this->getSectionHeaderStyle(),  // DOCUMENT INFORMATION
            34 => $this->getSectionHeaderStyle(),  // ADDRESS INFORMATION
            46 => $this->getSectionHeaderStyle(),  // BANK INFORMATION
            51 => $this->getSectionHeaderStyle(),  // ACCOUNT STATUS
            57 => $this->getSectionHeaderStyle(),  // VERIFICATION DETAILS
            62 => $this->getSectionHeaderStyle(),  // OTP INFORMATION
            69 => $this->getSectionHeaderStyle(),  // ACTIVITY LOGS

            // All cells border
            'A1:B' . $sheet->getHighestRow() => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ],

            // Field column (Column A) - Bold
            'A' => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
            ]
        ];
    }

    /**
     * Get section header style
     */
    private function getSectionHeaderStyle()
    {
        return [
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => '000000']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E3F2FD']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 35,  // Field column
            'B' => 55,  // Value column
        ];
    }
}
