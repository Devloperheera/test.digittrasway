<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class DlDetailsExport implements FromCollection, WithHeadings, WithStyles
{
    protected $dlData;

    public function __construct($dlData)
    {
        $this->dlData = $dlData;
    }

    public function collection()
    {
        $data = [];

        if (!empty($this->dlData)) {
            // Vehicle Classes
            $vehicleClasses = is_array($this->dlData['vehicle_classes'] ?? null)
                ? implode(', ', $this->dlData['vehicle_classes'])
                : 'N/A';

            // Gender formatting
            $gender = $this->dlData['gender'] ?? 'N/A';
            if ($gender === 'M') $gender = 'Male';
            elseif ($gender === 'F') $gender = 'Female';
            elseif ($gender === 'O') $gender = 'Other';

            // Transport dates (skip if default 1800-01-01)
            $transportDoi = ($this->dlData['transport_doi'] ?? '1800-01-01') !== '1800-01-01'
                ? $this->dlData['transport_doi']
                : 'N/A';

            $transportDoe = ($this->dlData['transport_doe'] ?? '1800-01-01') !== '1800-01-01'
                ? $this->dlData['transport_doe']
                : 'N/A';

            $data[] = [
                $this->dlData['client_id'] ?? 'N/A',
                $this->dlData['license_number'] ?? $this->dlData['dl_number'] ?? 'N/A',
                $this->dlData['name'] ?? 'N/A',
                $this->dlData['father_or_husband_name'] ?? 'N/A',
                $this->dlData['dob'] ?? 'N/A',
                $gender,
                !empty($this->dlData['blood_group']) ? $this->dlData['blood_group'] : 'N/A',
                $this->dlData['state'] ?? 'N/A',
                $this->dlData['ola_name'] ?? 'N/A',
                $this->dlData['ola_code'] ?? 'N/A',
                $this->dlData['permanent_address'] ?? 'N/A',
                !empty($this->dlData['permanent_zip']) ? $this->dlData['permanent_zip'] : 'N/A',
                $this->dlData['temporary_address'] ?? 'N/A',
                !empty($this->dlData['temporary_zip']) ? $this->dlData['temporary_zip'] : 'N/A',
                !empty($this->dlData['citizenship']) ? $this->dlData['citizenship'] : 'N/A',
                $this->dlData['doi'] ?? 'N/A',
                $this->dlData['doe'] ?? 'N/A',
                $transportDoi,
                $transportDoe,
                $vehicleClasses,
                ($this->dlData['has_image'] ?? false) ? 'Yes' : 'No',
                ($this->dlData['less_info'] ?? false) ? 'Yes' : 'No',
                $this->dlData['verified_at'] ?? now()->format('d M Y, h:i A')
            ];
        }

        return new Collection($data);
    }

    public function headings(): array
    {
        return [
            'Client ID',
            'DL Number',
            'Name',
            'Father/Husband Name',
            'Date of Birth',
            'Gender',
            'Blood Group',
            'State',
            'OLA Name',
            'OLA Code',
            'Permanent Address',
            'Permanent ZIP',
            'Temporary Address',
            'Temporary ZIP',
            'Citizenship',
            'Issue Date (Non-Transport)',
            'Expiry Date (Non-Transport)',
            'Issue Date (Transport)',
            'Expiry Date (Transport)',
            'Vehicle Classes',
            'Has Profile Image',
            'Less Info',
            'Verified At'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '667eea']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
        ];
    }
}
