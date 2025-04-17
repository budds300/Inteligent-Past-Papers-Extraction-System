<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Intelligent Past Papers Extraction System') }}</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="max-w-6xl mx-auto px-4 py-6">
        <nav class="bg-blue-600 text-white rounded-lg mb-6 shadow-lg">
            <div class="flex items-center justify-between p-4">
                <a href="{{ url('/') }}" class="text-lg font-bold">Intelligent Past Papers</a>
                <ul class="flex space-x-6">
                    <li>
                        <a href="{{ route('question-papers.index') }}" class="hover:underline">All Papers</a>
                    </li>
                    <li>
                        <a href="{{ route('question-papers.create') }}" class="hover:underline">Upload</a>
                    </li>
                </ul>
            </div>
        </nav>

        @yield('content')
    </div>

</body>
</html>
