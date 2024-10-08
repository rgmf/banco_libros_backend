<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información sobre el préstamo</title>
    <style>
        .title {
            font-size: 1.5rem;
            margin: 10 0 10 0;
        }

        table {
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid gray;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <p>
        Querido equipo del Banco de Libros,
    </p>

    <p>
        se ha realizado un préstamo al/a la alumno/a:
    </p>

    @if ($lendings && $lendings[0]->student)
        <p><strong>Alumno/a</strong>: {{ $lendings[0]->student->name }} {{ $lendings[0]->student->lastname1 }} {{ $lendings[0]->student->lastname2 }}</p>
        <p><strong>Curso</strong>: {{ $lendings[0]->student->cohort->name }}</p>
    @else
        <p>No hay datos del alumnado</p>
    @endif

    <p>
        con los siguientes libros en el estado en que se indica:
    </p>

    @forelse ($lendings as $lending)
        @if ($loop->first)
            <div style="overflow-x:auto;">
            <table>
            <tr><th>Título</th><th>Editorial</th><th>Nº de libros</th><th>Estado</th><th>Observaciones</th></tr>
        @endif

        <tr>
            <td style="padding: 15px">
                {{ $lending->bookCopy->book->title }}
            </td>
            <td style="padding: 15px">
                {{ $lending->bookCopy->book->publisher }}
            </td>
            <td style="padding: 15px">
                {{ $lending->bookCopy->book->volumes }}
            </td>
            <td style="padding: 15px">
                {{ $lending->bookCopy->status->name }}
            </td>
            <td style="padding: 15px">
                @isset ($lending->bookCopy->observations)
                    @foreach ($lending->bookCopy->observations as $observation)
                        <div>- {{ $observation->title }}</div>
                    @endforeach
                @endisset

                @isset ($lending->bookCopy->comment)
                    <div>- {{ $lending->bookCopy->comment }}</div>
                @endisset
            </td>
        </tr>

        @if ($loop->last)
            </table>
            </div>
        @endif
    @empty
        <p>No hay préstamos</p>
    @endforelse

    <p>
        Pero no se ha podido enviar e-mail con la información ni al padre ni a la madre porque
        no disponemos de esta información para este/a alumno/a.
    </p>

    <p>
        Banco de Libros (IES La Encantá)<br>
        <small>bancodelibros@ieslaencanta.com</small>
    </p>
</body>
</html>
