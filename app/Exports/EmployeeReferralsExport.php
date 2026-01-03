<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeReferralsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $referrals;
    protected $employee;

    public function __construct($referrals, $employee)
    {
        $this->referrals = $referrals;
        $this->employee = $employee;
    }

    /**
     * Return collection of referrals
     */
    public function collection()
    {
        return $this->referrals;
    }

    /**
     * Define Excel column headings
     */
    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'User Name',
            'Contact Number',
            'Email',
            'Aadhaar Number',
            'PAN Number',
            'Install Date',
            'Verification Status',
            'Completion Status',
            'Address',
            'City',
            'State',
            'Pincode',
            'Registration Date',
        ];
    }

    /**
     * Map data for each row
     */
    public function map($user): array
    {
        return [
            $this->employee->emp_id,
            $this->employee->name,
            $user->name ?? 'N/A',
            $user->contact_number,
            $user->email ?? 'N/A',
            $user->aadhar_number ?? 'N/A',
            $user->pan_number ?? 'N/A',
            $user->app_installed_at ? $user->app_installed_at->format('d-m-Y H:i:s') : 'N/A',
            $user->is_verified ? 'Verified' : 'Pending',
            $user->is_completed ? 'Completed' : 'Incomplete',
            $user->full_address ?? 'N/A',
            $user->city ?? 'N/A',
            $user->state ?? 'N/A',
            $user->pincode ?? 'N/A',
            $user->created_at ? $user->created_at->format('d-m-Y H:i:s') : 'N/A',
        ];
    }

    /**
     * Apply styles to the sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Set sheet title
     */
    public function title(): string
    {
        return 'Referrals_' . $this->employee->emp_id;
    }
}
