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
        Estimada familia,
    </p>

    <p>
        el Banco de Libros del IES La Encantá (Rojales) presta al alumno/a:
    </p>

    @if ($lendings && $lendings[0]->student)
        <p><strong>Alumno/a</strong>: {{ $lendings[0]->student->name }} {{ $lendings[0]->student->lastname1 }} {{ $lendings[0]->student->lastname2 }}</p>
        <p><strong>Curso</strong>: {{ $lendings[0]->student->cohort->name }}</p>
    @else
        <p>No hay datos del alumnado</p>
    @endif

    <p>
        los siguientes libros en el estado en que se indica:
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
        <strong>Le recordamos que al recibir el lote de libros se comprometen a hacer entrega del mismo,
        al finalizar el curso, en buen estado: forrado rígido para su buena conservación, sin 
        subrayado, sin suciedad y sin hojas mojadas.</strong>
    </p>

    <p>
        <strong>El libro que no cumpla estas condiciones deberá ser repuesto por la familia o será excluido del
        Banco de Libros.</strong>
    </p>

    <p>
        Si en un plazo de 7 días no recibimos ninguna notificación por su parte, entenderemos
        que están de acuerdo con la información que aporta este correo y servirá como justificante
        en el momento de la devolución de los libros.
    </p>

    <p>
        En caso de rectificación o ampliación de esta información, pónganse en contacto con el
        Banco de Libros respondiendo a este correo.
    </p>

    <p>
        Banco de Libros (IES La Encantá)<br>
        <small>bancodelibros@ieslaencanta.com</small>
    </p>
</body>
</html>
