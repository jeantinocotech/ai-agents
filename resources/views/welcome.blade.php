<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agentes AI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <header class="bg-white shadow">
        <div class="container mx-auto px-4 py-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Meus Agentes AI</h1>
            <div>
                <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 mr-4">Entrar</a>
                <a href="{{ route('register') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Registrar</a>
            </div>
        </div>
    </header>

    <main class="flex-grow container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Nossos Agentes Disponíveis</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            <!-- Card de Produto -->
            <div class="bg-white rounded-lg shadow p-6 flex flex-col">
                <img src="/images/agent1.jpg" alt="Agente 1" class="rounded mb-4">
                <h3 class="text-xl font-semibold mb-2">Agente Criador de Conteúdo</h3>
                <p class="text-gray-600 mb-4">Crie textos, posts e artigos incríveis em segundos.</p>
                <div class="text-lg font-bold mb-4">R$ 49,90</div>
                <a href="#" class="mt-auto bg-blue-500 hover:bg-blue-600 text-white text-center py-2 rounded">Ver Mais</a>
            </div>

            <!-- Repetir cards conforme necessário -->
            <div class="bg-white rounded-lg shadow p-6 flex flex-col">
                <img src="/images/agent2.jpg" alt="Agente 2" class="rounded mb-4">
                <h3 class="text-xl font-semibold mb-2">Agente de Atendimento</h3>
                <p class="text-gray-600 mb-4">Responda clientes automaticamente com qualidade.</p>
                <div class="text-lg font-bold mb-4">R$ 59,90</div>
                <a href="#" class="mt-auto bg-blue-500 hover:bg-blue-600 text-white text-center py-2 rounded">Ver Mais</a>
            </div>

            <div class="bg-white rounded-lg shadow p-6 flex flex-col">
                <img src="/images/agent3.jpg" alt="Agente 3" class="rounded mb-4">
                <h3 class="text-xl font-semibold mb-2">Agente de Pesquisa</h3>
                <p class="text-gray-600 mb-4">Busque informações e referências automaticamente.</p>
                <div class="text-lg font-bold mb-4">R$ 39,90</div>
                <a href="#" class="mt-auto bg-blue-500 hover:bg-blue-600 text-white text-center py-2 rounded">Ver Mais</a>
            </div>
        </div>
    </main>

    <footer class="bg-white shadow mt-12">
        <div class="container mx-auto px-4 py-6 text-center text-gray-500 text-sm">
            &copy; {{ date('Y') }} Meus Agentes AI. Todos os direitos reservados.
        </div>
    </footer>

</body>
</html>
