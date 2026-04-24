<x-app-layout>
    <section class="hero">
        <h2>Panel del administrador</h2>
        <p>Bienvenido, {{ auth()->user()->name }}.</p>
        <p>Desde aquí podrás administrar usuarios, contenido y avances.</p>
    </section>
</x-app-layout>