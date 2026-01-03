<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class RcDetailsExport implements FromCollection, WithHeadings, WithStyles
{
    protected $rcData;

    public function __construct($rcData)
    {
        $this->rcData = $rcData;
    }

    public function collection()
    {
        $data = [];

        if (!empty($this->rcData)) {
            $data[] = [
                // Basic Information
                $this->rcData['client_id'] ?? 'N/A',
                $this->rcData['rc_number'] ?? 'N/A',
                $this->rcData['registration_date'] ?? 'N/A',
                $this->rcData['registered_at'] ?? 'N/A',
                $this->rcData['rc_status'] ?? 'N/A',

                // Owner Information
                $this->rcData['owner_name'] ?? 'N/A',
                $this->rcData['father_name'] ?? 'N/A',
                !empty($this->rcData['mobile_number']) ? $this->rcData['mobile_number'] : 'N/A',
                $this->rcData['present_address'] ?? 'N/A',
                $this->rcData['permanent_address'] ?? 'N/A',
                $this->rcData['owner_number'] ?? 'N/A',

                // Vehicle Information
                $this->rcData['vehicle_category'] ?? 'N/A',
                $this->rcData['vehicle_category_description'] ?? 'N/A',
                $this->rcData['maker_description'] ?? 'N/A',
                $this->rcData['maker_model'] ?? 'N/A',
                $this->rcData['variant'] ?? 'N/A',
                $this->rcData['body_type'] ?? 'N/A',
                $this->rcData['color'] ?? 'N/A',
                $this->rcData['vehicle_chasi_number'] ?? 'N/A',
                $this->rcData['vehicle_engine_number'] ?? 'N/A',
                $this->rcData['fuel_type'] ?? 'N/A',
                $this->rcData['norms_type'] ?? 'N/A',
                $this->rcData['manufacturing_date'] ?? 'N/A',
                $this->rcData['manufacturing_date_formatted'] ?? 'N/A',

                // Vehicle Specifications
                $this->rcData['cubic_capacity'] ?? 'N/A',
                $this->rcData['no_cylinders'] ?? 'N/A',
                $this->rcData['vehicle_gross_weight'] ?? 'N/A',
                $this->rcData['unladen_weight'] ?? 'N/A',
                $this->rcData['wheelbase'] ?? 'N/A',
                $this->rcData['seat_capacity'] ?? 'N/A',
                $this->rcData['sleeper_capacity'] ?? 'N/A',
                $this->rcData['standing_capacity'] ?? 'N/A',

                // Insurance Information
                $this->rcData['insurance_company'] ?? 'N/A',
                $this->rcData['insurance_policy_number'] ?? 'N/A',
                $this->rcData['insurance_upto'] ?? 'N/A',

                // Finance Information
                isset($this->rcData['financed']) && $this->rcData['financed'] ? 'Yes' : 'No',
                $this->rcData['financer'] ?? 'N/A',

                // Permit Information
                $this->rcData['permit_number'] ?? 'N/A',
                $this->rcData['permit_type'] ?? 'N/A',
                $this->rcData['permit_issue_date'] ?? 'N/A',
                $this->rcData['permit_valid_from'] ?? 'N/A',
                $this->rcData['permit_valid_upto'] ?? 'N/A',
                $this->rcData['national_permit_number'] ?? 'N/A',
                $this->rcData['national_permit_upto'] ?? 'N/A',
                $this->rcData['national_permit_issued_by'] ?? 'N/A',

                // Tax & Fitness
                $this->rcData['tax_upto'] ?? 'N/A',
                $this->rcData['tax_paid_upto'] ?? 'N/A',
                $this->rcData['fit_up_to'] ?? 'N/A',
                !empty($this->rcData['pucc_number']) ? $this->rcData['pucc_number'] : 'N/A',
                $this->rcData['pucc_upto'] ?? 'N/A',

                // Additional Information
                $this->rcData['blacklist_status'] ?? 'N/A',
                $this->rcData['non_use_status'] ?? 'N/A',
                $this->rcData['non_use_from'] ?? 'N/A',
                $this->rcData['non_use_to'] ?? 'N/A',
                $this->rcData['noc_details'] ?? 'N/A',
                $this->rcData['challan_details'] ?? 'N/A',

                // Verification Status
                isset($this->rcData['masked_name']) && $this->rcData['masked_name'] ? 'Yes' : 'No',
                isset($this->rcData['less_info']) && $this->rcData['less_info'] ? 'Yes' : 'No',
                $this->rcData['latest_by'] ?? 'N/A',
                $this->rcData['verified_at'] ?? now()->format('d M Y, h:i A'),
                isset($this->rcData['government_verified']) && $this->rcData['government_verified'] ? 'Yes' : 'No'
            ];
        }

        return new Collection($data);
    }

    public function headings(): array
    {
        return [
            // Basic Information
            'Client ID',
            'RC Number',
            'Registration Date',
            'Registered At',
            'RC Status',

            // Owner Information
            'Owner Name',
            'Father Name',
            'Mobile Number',
            'Present Address',
            'Permanent Address',
            'Owner Number',

            // Vehicle Information
            'Vehicle Category',
            'Vehicle Category Description',
            'Maker Description',
            'Maker Model',
            'Variant',
            'Body Type',
            'Color',
            'Chassis Number',
            'Engine Number',
            'Fuel Type',
            'Norms Type',
            'Manufacturing Date',
            'Manufacturing Date Formatted',

            // Vehicle Specifications
            'Cubic Capacity (CC)',
            'Number of Cylinders',
            'Vehicle Gross Weight (kg)',
            'Unladen Weight (kg)',
            'Wheelbase (mm)',
            'Seat Capacity',
            'Sleeper Capacity',
            'Standing Capacity',

            // Insurance Information
            'Insurance Company',
            'Insurance Policy Number',
            'Insurance Valid Upto',

            // Finance Information
            'Financed',
            'Financer',

            // Permit Information
            'Permit Number',
            'Permit Type',
            'Permit Issue Date',
            'Permit Valid From',
            'Permit Valid Upto',
            'National Permit Number',
            'National Permit Upto',
            'National Permit Issued By',

            // Tax & Fitness
            'Tax Upto',
            'Tax Paid Upto',
            'Fit Up To',
            'PUCC Number',
            'PUCC Upto',

            // Additional Information
            'Blacklist Status',
            'Non Use Status',
            'Non Use From',
            'Non Use To',
            'NOC Details',
            'Challan Details',

            // Verification Status
            'Masked Name',
            'Less Info',
            'Latest By',
            'Verified At',
            'Government Verified'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '11998e']
                ]
            ],
        ];
    }
}
