<?php

namespace Database\Seeders;

use App\Models\AiDocument;
use Illuminate\Database\Seeder;

class AiDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            [
                'order'   => 1,
                'title'   => 'Hambre emocional vs. hambre real',
                'source'  => 'Marco clínico — Dr. Máximo Ravenna',
                'content' => <<<EOT
El hambre real es un proceso fisiológico que aparece de forma gradual cuando el organismo necesita energía. Se instala lentamente, no discrimina alimentos y se calma con cualquier comida nutritiva. No genera urgencia ni angustia: es una señal corporal clara y tolerable.

El hambre emocional, en cambio, es un estado de urgencia que no proviene del cuerpo sino del mundo interno. Aparece de forma brusca, es altamente selectiva (quiere algo específico, generalmente dulce o ultraprocesado), y no se calma con la ingesta porque no es una necesidad calórica sino afectiva. Detrás del hambre emocional siempre hay un disparador: ansiedad, tristeza, aburrimiento, soledad, enojo no expresado o situaciones de tensión interpersonal.

La confusión entre ambos tipos de hambre es el núcleo del trastorno de la conducta alimentaria en el paciente con obesidad. El trabajo terapéutico consiste en enseñar al paciente a identificar el disparador emocional antes de comer, a nombrarlo y a desarrollar una respuesta alternativa que no involucre la comida.

Para el coordinador: cuando un paciente reporta aumento de peso o ingesta descontrolada en la semana, la primera intervención es explorar qué estaba sintiendo antes de comer, no qué comió. El qué es consecuencia; el para qué es la clave clínica.
EOT,
            ],
            [
                'order'   => 2,
                'title'   => 'El cuerpo como síntoma',
                'source'  => 'Marco clínico — Dr. Máximo Ravenna',
                'content' => <<<EOT
En el abordaje de Ravenna, el exceso de peso no es un defecto de carácter ni una falta de voluntad: es un síntoma. El cuerpo expresa aquello que la psique no puede metabolizar de otro modo. La sobrealimentación es la solución que el sujeto encontró, muchas veces desde la infancia, para regular un estado interno que no sabe cómo manejar de otra manera.

Esto implica que tratar la obesidad sin tratar el mundo emocional del paciente es tratar el síntoma sin tocar la causa. El peso puede bajar por un tiempo, pero si no se trabaja el vínculo del paciente con su mundo afectivo, la recaída es inevitable.

El síntoma tiene una función: calma, anestesia, protege, llena un vacío. Antes de pedirle al paciente que abandone esa conducta, el trabajo terapéutico debe ayudarlo a entender qué función cumple en su vida y construir recursos alternativos para satisfacer esa necesidad.

Para el coordinador: cuando un paciente no logra sostener el descenso o vuelve a ganar peso, la pregunta clínica es "¿qué está protegiendo ese peso?" o "¿qué necesidad no está pudiendo cubrir de otra manera?". La respuesta nunca es "falta de voluntad".
EOT,
            ],
            [
                'order'   => 3,
                'title'   => 'La sobrealimentación como conducta adictiva',
                'source'  => 'Marco clínico — Dr. Máximo Ravenna',
                'content' => <<<EOT
Ravenna conceptualiza la obesidad compulsiva como una conducta con estructura adictiva. Al igual que en otras adicciones, se observa un ciclo repetitivo: tensión interna → búsqueda del alimento → consumo → alivio momentáneo → culpa → nueva tensión → recaída. El alimento funciona como la sustancia: es la respuesta aprendida para regular el malestar.

Este enfoque tiene implicancias clínicas concretas. Primero, el paciente no es "débil de voluntad": está atrapado en un circuito neurobiológico y psicológico que opera de forma automática. Segundo, la abstinencia total (como en el alcohol) no es posible porque hay que comer para vivir; por lo tanto, el trabajo es aprender a relacionarse con la comida de forma consciente y no compulsiva. Tercero, las recaídas son parte del proceso, no fracasos definitivos.

El tratamiento grupal es especialmente eficaz en conductas adictivas porque replica la estructura de los grupos de autoayuda: identificación con el otro, ruptura del aislamiento y vergüenza, acompañamiento sostenido en el tiempo.

Para el coordinador: ante una recaída, evitar el discurso punitivo o la sorpresa. Normalizar el tropiezo, explorar el disparador y reforzar la continuidad del proceso. La asistencia al grupo después de una recaída es en sí misma un acto terapéutico.
EOT,
            ],
            [
                'order'   => 4,
                'title'   => 'El grupo como sostén terapéutico',
                'source'  => 'Marco clínico — Dr. Máximo Ravenna',
                'content' => <<<EOT
El dispositivo grupal es el corazón del método Ravenna. El grupo no es una clase de nutrición ni una reunión de control de peso: es un espacio terapéutico donde opera la identificación, el sostén y la elaboración colectiva. Cada paciente siente que no está solo con su problema, y eso en sí mismo tiene un efecto clínico poderoso.

Los mecanismos terapéuticos del grupo incluyen: la identificación con pares que atraviesan la misma lucha, la experiencia de ser aceptado sin ser juzgado, la posibilidad de verse reflejado en el otro (lo que en el otro reconozco como problema también es lo mío), y el sostén afectivo sostenido en el tiempo.

La regularidad en la asistencia es, por sí sola, un acto terapéutico. Venir al grupo aunque la semana haya sido mala, aunque se haya subido de peso, aunque no haya ganas, es el ejercicio de la constancia que el paciente necesita desarrollar como nuevo recurso de vida.

Para el coordinador: la ausencia de un paciente debe ser leída clínicamente, no solo logísticamente. Generalmente, el paciente que falta es el que más necesita el espacio grupal. Se recomienda el contacto activo ante ausencias repetidas y explorar, al regreso, qué impidió venir.
EOT,
            ],
            [
                'order'   => 5,
                'title'   => 'El rol del coordinador en el grupo terapéutico',
                'source'  => 'Marco clínico — Dr. Máximo Ravenna',
                'content' => <<<EOT
El coordinador no es un controlador de peso ni un instructor: es un facilitador terapéutico. Su función principal es crear y sostener un espacio donde los pacientes puedan hablar de su mundo interno sin vergüenza, conectar sus conductas alimentarias con sus emociones, y sentirse acompañados en el proceso de cambio.

Las competencias clínicas clave del coordinador incluyen: la observación del estado emocional del paciente más allá del número en la balanza, la capacidad de hacer preguntas que abran el diálogo interno ("¿cómo estuviste esta semana?", "¿qué pasó antes de comer eso?"), la tolerancia a los momentos de estancamiento o retroceso sin transmitir alarma o decepción, y el manejo del vínculo grupal para que sea un espacio seguro.

El coordinador debe poder distinguir entre un plateau fisiológico (normal en el proceso de descenso) y un estancamiento emocional (el peso estabilizado como defensa). Ante el segundo, la intervención es explorar qué cambio de vida se está resistiendo.

Un coordinador eficaz usa el número del peso como punto de entrada, no como punto final. La pregunta no es solo "¿cuánto bajaste?" sino "¿cómo te sentís con tu cuerpo esta semana?", "¿qué situación te costó más sostener?", "¿qué lograste que antes no podías?".
EOT,
            ],
        ];

        foreach ($documents as $doc) {
            AiDocument::firstOrCreate(
                ['title' => $doc['title']],
                array_merge($doc, ['active' => true])
            );
        }

        $this->command->info('✓ 5 fragmentos de bibliografía Ravenna cargados.');
    }
}
