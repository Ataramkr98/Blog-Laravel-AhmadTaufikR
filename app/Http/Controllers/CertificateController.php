<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function show(Certificate $certificate): View
    {
        abort_if($certificate->revoked_at !== null, 404);

        $certificate->load(['course', 'user', 'attempt']);

        return view('certificates.show', compact('certificate'));
    }
}
