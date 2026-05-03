<x-app-layout>
    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                @include('agents.documents.partials.library-forms', [
                    'agent' => $agent,
                    'profileCvs' => $profileCvs,
                    'jds' => $jds,
                    'defaults' => $defaults,
                    'maxCvBodyChars' => $maxCvBodyChars,
                    'maxJdBodyChars' => $maxJdBodyChars,
                    'trailReturnCareerTrailAts' => false,
                    'suppressSessionFlash' => false,
                    'omitHeading' => false,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
