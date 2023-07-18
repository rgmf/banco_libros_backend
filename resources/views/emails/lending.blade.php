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
            /*border-collapse: collapse;*/
            border: 0;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <p>
        Hola,
    </p>

    <p>
        desde el Banco de Libros del IES La Encantá (Rojales) se ha hecho un préstamo al alumno/a que
        se indica más abajo. Revisa toda la información y ponte en contacto con el Banco de Libros,
        <strong>cuanto antes</strong>, si ves un error.
    </p>

    @if ($lendings && $lendings[0]->student)
        <p><strong>Alumno/a</strong>: {{ $lendings[0]->student->name }} {{ $lendings[0]->student->lastname1 }} {{ $lendings[0]->student->lastname2 }}</p>
        <p><strong>Curso</strong>: {{ $lendings[0]->student->cohort->name }}</p>
    @else
        <p>No hay datos del alumnado</p>
    @endif

    <p>
        Ten en cuenta que, con este préstamo, aceptas el estado del lote de libros y te comprometes
        a hacer entrega del mismo, al finalizar el curso, en buen estado: forrado rígido para su buena
        conservación y sin subrayado. El libro que no cumpla estas condiciones deberá ser repuesto por
        la familia.
    </p>
    <p>
        Valoremos que su coste asciende a 185€, que contribuimos a cuidar el medio
        ambiente y que nos gusta recibir libros en buen estado.
    </p>
    <p>
        Aquí están los libros, en el estado en que se te han prestado y, por tanto, el estado en que los
        tendrás que devolver:
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
        Banco de Libros (IES La Encantá)<br>
        <small>bancodelibros@ieslaencanta.com</small>
    </p>
</body>
</html>
