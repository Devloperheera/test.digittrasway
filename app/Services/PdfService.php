<?php

namespace App\Services;

use Mpdf\Mpdf;

class PdfService
{
    /**
     * Generate Vendors PDF
     */
    public function generateVendorsPdf($vendors)
    {
        $html = view('pdfs.vendors', compact('vendors'))->render();

        $mpdf = new Mpdf([
            'format' => 'A4-L', // Landscape
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
            'margin_right' => 10
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('vendors_list.pdf', 'D');
    }

    /**
     * Generate RC Details PDF
     */
    public function generateRcDetailsPdf($vendor, $rcData)
    {
        $html = view('pdfs.rc_details', compact('vendor', 'rcData'))->render();

        $mpdf = new Mpdf(['format' => 'A4']);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('rc_details_' . $vendor->id . '.pdf', 'D');
    }

    /**
     * Generate DL Details PDF
     */
    public function generateDlDetailsPdf($vendor, $dlData)
    {
        $html = view('pdfs.dl_details', compact('vendor', 'dlData'))->render();

        $mpdf = new Mpdf(['format' => 'A4']);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('dl_details_' . $vendor->id . '.pdf', 'D');
    }
}
