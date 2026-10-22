<p>Os relatorios automaticos do curso <strong>{{ $cursoTitulo }}</strong> foram gerados.</p>

<p>Os arquivos selecionados na configuracao estao anexados a esta mensagem:</p>

<ul>
    @foreach ($reports as $report)
        <li>{{ $report['label'] }}</li>
    @endforeach
</ul>
