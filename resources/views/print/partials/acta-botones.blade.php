<div class="no-print">
    <button class="btn btn-primary" onclick="window.print()">Imprimir / Guardar PDF</button>
    <a href="{{ route('actas.export.word', $acta->id) }}" class="btn btn-green">Descargar Word</a>
    <a href="{{ route('actas.export.pdf',  $acta->id) }}" class="btn btn-red">Descargar PDF</a>
    <button class="btn btn-gray" onclick="window.close()">Cerrar</button>
</div>
