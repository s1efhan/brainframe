<template>
    <div class="survey__container">
        <h1>Umfrage zur Nutzung von anonymen, KI-unterstützten, digital-strukturierten Ideen-Findungstools am Beispiel
            (BrainFrame)</h1>
            <h2>Frage {{ currentIndex + 1 }} von {{ totalQuestions }}</h2>
            <div v-if="currentQuestion">
      <div class="current_question" v-if="currentQuestion.type === 'quantitative'">
    <p class="current_question__label">{{ currentQuestion.label }}</p>
    <div class="options" v-for="option in ratingOptions" :key="option.id">
        <input type="radio" 
               :id="currentQuestion.key + '_' + option.id" 
               :name="currentQuestion.key"
               v-model="surveyData[currentQuestion.key]" 
               :value="option.id"
               @change="autoNext">
        <label :for="currentQuestion.key + '_' + option.id">{{ option.label }}</label>
    </div>
</div>

            <div v-else-if="currentQuestion.type === 'qualitative'">
                <label>{{ currentQuestion.label }}</label>
                <textarea v-model="surveyData[currentQuestion.key]"></textarea>
            </div>

            <div v-else-if="currentQuestion.type === 'checkbox'">
                <h3>{{ currentQuestion.label }}</h3>
                <div v-for="method in currentQuestion.options" :key="method.key">
                    <input type="checkbox" :id="method.key" v-model="surveyData[method.key]">
                    <label :for="method.key">{{ method.label }}</label>
                </div>
            </div>

            <div v-else-if="currentQuestion.type === 'demographic'">
                <label>{{ currentQuestion.label }}</label>
                <input :type="currentQuestion.inputType" v-model="surveyData[currentQuestion.key]">
            </div>
        </div>

        <div class="navigation">
            <button class="accent" @click="previousQuestion" :disabled="currentIndex === 0">Zurück</button>
            <button class="primary" @click="nextQuestion" v-if="currentIndex < totalQuestions - 1">Weiter</button>
            <button class="primary" @click="submitSurvey" v-else>Umfrage abschließen</button>
        </div>
        <p class="submitted_count">{{ answeredQuestions.length }} / {{ questions.length }}</p>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const sessionId = parseInt(route.params.sessionId, 10);  // Konvertiere zu Integer
const router = useRouter();
const props = defineProps({
  userId: {
    type: Number,
    required: true
  }
});
const autoNext = async () => {
    await saveAnswer();
    if (currentIndex.value < totalQuestions.value - 1) {
        currentIndex.value++;
    }
};
const ratingOptions = [
    { id: 1, label: 'Stimme überhaupt nicht zu' },
    { id: 2, label: 'Stimme eher nicht zu' },
    { id: 3, label: 'Neutral' },
    { id: 4, label: 'Stimme eher zu' },
    { id: 5, label: 'Stimme voll zu' },
];

const questions = [
    { key: 'ideas_novelty_relevance', type: 'quantitative', label: 'Die generierten Ideen waren neuartig und relevant für die Aufgabenstellung.' },
    { key: 'ideas_quantity_diversity', type: 'quantitative', label: 'Die Anzahl und Vielfalt der Ideen war zufriedenstellend.' },
    { key: 'tool_ease_of_use', type: 'quantitative', label: 'Das Tool war einfach zu bedienen.' },
    { key: 'tool_thought_organization', type: 'quantitative', label: 'Die Struktur des Tools half mir, meine Gedanken zu organisieren.' },
    { key: 'anonymous_input_openness', type: 'quantitative', label: 'Die anonyme Eingabe ermutigte mich, offener Ideen zu teilen.' },
    { key: 'ai_support_helpfulness', type: 'quantitative', label: 'Die KI-Unterstützung war hilfreich für die Ideengenerierung.' },
    { key: 'ai_suggestions_relevance', type: 'quantitative', label: 'Die KI-generierten Vorschläge waren relevant und inspirierend.' },
    { key: 'ai_inspiration', type: 'quantitative', label: 'Die KI-Vorschläge inspirierten mich zu eigenen, weiterführenden Ideen.' },
    { key: 'structure_method_facilitation', type: 'quantitative', label: 'Die Struktur der Anwendung hat mir den Umgang mit der gewählten Brainstorm-Methode erleichtert.' },
    { key: 'tool_effectiveness', type: 'quantitative', label: 'Dieses Tool ist effektiver als traditionelle Brainstorming-Methoden.' },
    { key: 'idea_evaluation_transparency', type: 'quantitative', label: 'Die Bewertung von Ideen mit dem Tool war transparent und nachvollziehbar.' },
    { key: 'rating_methods_understandability', type: 'quantitative', label: 'Die verfügbaren Bewertungsmethoden waren leicht verständlich.' },
    { key: 'result_pdf_usefulness', type: 'quantitative', label: 'Ein automatisch generiertes Ergebnis-PDF ist nützlich für die Nachbereitung und Weiterleitung von Brainstorming-Ergebnissen.' },
    { key: 'result_pdf_clarity', type: 'quantitative', label: 'Das Ergebnis-PDF fasst die Session-Ergebnisse klar und verständlich zusammen.' },
    { key: 'tool_future_use', type: 'quantitative', label: 'Ich würde dieses Tool für zukünftige Ideenfindungssessions wieder nutzen.' },
    { key: 'tool_recommendation', type: 'quantitative', label: 'Ich würde dieses Tool anderen für Ideenfindungsprozesse empfehlen.' },
    { key: 'session_expectations', type: 'quantitative', label: 'Die Ergebnisse der Session entsprachen meinen Erwartungen oder übertrafen sie.' },
    {
        key: 'known_methods',
        type: 'checkbox',
        label: 'Welche Methoden kannten Sie bereits?',
        options: [
            { key: 'known_method_635', label: '6-3-5 Methode' },
            { key: 'known_method_walt_disney', label: 'Walt Disney Methode' },
            { key: 'known_method_crazy_8', label: 'Crazy 8 Methode' },
            { key: 'known_method_brainstorming', label: 'Klassisches Brainstorming' },
            { key: 'known_method_6_thinking_hats', label: '6 Thinking Hats / 6 Denkhüte' },
        ]
    },
    { key: 'valuable_aspects', type: 'qualitative', label: 'Welche Aspekte des digitalen, strukturierten und anonymen Ideenfindungsprozesses (einschließlich der KI-Unterstützung) haben Sie als besonders wertvoll empfunden und warum?' },
    { key: 'desired_improvements', type: 'qualitative', label: 'Welche zusätzlichen Funktionen oder Verbesserungen würden Sie sich für zukünftige digitale Ideenfindungstools wünschen und warum?' },
    { key: 'unexpected_benefits_challenges', type: 'qualitative', label: 'Welche unerwarteten Vorteile oder Herausforderungen haben Sie bei der Nutzung dieses Tools erlebt?' },
    { key: 'additional_comments', type: 'qualitative', label: 'Sonstige Anmerkungen:' },
    { key: 'age', type: 'demographic', label: 'Alter', inputType: 'number' },
    { key: 'occupation', type: 'demographic', label: 'Beruf', inputType: 'text' },
    { key: 'industry', type: 'demographic', label: 'Branche', inputType: 'text' },
];

const surveyData = ref({});
const currentIndex = ref(0);

const currentQuestion = computed(() => questions[currentIndex.value]);
const totalQuestions = computed(() => questions.length);
const findFirstUnansweredQuestion = () => {
  return questions.findIndex(question => {
    if (question.type === 'checkbox') {
      return question.options.some(option => !surveyData.value[option.key]);
    } else {
      return !surveyData.value[question.key];
    }
  });
};
const loadSurveyData = async () => {
  try {
    const response = await axios.get(`/api/survey/${sessionId}/${props.userId}`);
    const data = response.data;
    
    Object.keys(data).forEach(key => {
      if (data[key] !== null) {
        surveyData.value[key] = data[key];
      }
    });
    
    // Initialisieren Sie Checkbox-Fragen
    questions.forEach(question => {
      if (question.type === 'checkbox') {
        question.options.forEach(option => {
          if (!(option.key in surveyData.value)) {
            surveyData.value[option.key] = false;
          }
        });
      }
    });
    
    // Setzen Sie den currentIndex auf die erste unbeantwortete Frage
    const firstUnansweredIndex = findFirstUnansweredQuestion();
    if (firstUnansweredIndex !== -1) {
      currentIndex.value = firstUnansweredIndex;
    }
    
    console.log("loadSurveyData", surveyData.value);
  } catch (error) {
    console.error('Error fetching survey-data', error);
  }
};

const saveAnswer = async () => {
    if (!surveyData.value[currentQuestion.value.key]) {
        console.error('Keine Antwort ausgewählt');
        return;
    }

    try {
        await axios.post('/api/survey/store', {
            session_id: sessionId,
            user_id: props.userId,
            question_key: currentQuestion.value.key,
            answer_value: surveyData.value[currentQuestion.value.key]
        });
    } catch (error) {
        console.error('Fehler beim Speichern der Antwort:', error);
    }
};

const nextQuestion = async () => {
    if (currentQuestion.value.type === 'checkbox') {
        // Für Checkbox-Fragen, sende jede ausgewählte Option einzeln
        for (const option of currentQuestion.value.options) {
            if (surveyData.value[option.key]) {
                await saveAnswer(option.key, 1);  // 1 für ausgewählt
            }
        }
    } else if (currentQuestion.value.type !== 'quantitative') {
        await saveAnswer();
    }
    if (currentIndex.value < totalQuestions.value - 1) {
        currentIndex.value++;
    }
};

const previousQuestion = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    }
};
const answeredQuestions = computed(() => {
  return questions.filter(question => {
    if (question.type === 'checkbox') {
      return question.options.some(option => surveyData.value[option.key]);
    } else {
      return surveyData.value[question.key] !== undefined && surveyData.value[question.key] !== null;
    }
  });
});
onMounted(() => {
    loadSurveyData();
});

</script>