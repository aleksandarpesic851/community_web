<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Questionaire;
use App\Models\QuestionaireQuestion;
use App\Models\QuestionaireAnswer;
use App\Models\QuestionaireQuestionOption;

class QuestionaireController extends GeneralController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if(Auth::check())
        {
            $questionaires = Questionaire::get();
        }
        else
        {
            $questionaires = Questionaire::where('access_level', 0)->get();
        }
        $data = [
            'questionaires' => $questionaires
        ];
        return view($this->templatePath . '.questionaire')
            ->with($data);
    }

    public function detail($questionaire_id, $method = '')
    {
        $questionaire = Questionaire::find($questionaire_id)->toArray();
        if (Auth::check())
        {
            $user = Auth::user();
            $answers = $user->questionaireAnswers();
            $answeredIds = $user->questionaireAnswers()->groupBy('questionaire_id')->pluck('questionaire_id')->toArray();
        }
        elseif($questionaire["access_level"] == 1)
        {
            return redirect(url('/'));
        }

        
        // if current user didn't answer for this questionaire, show questionaire edit page
        // Or if current user is not login and want to edit
        if ( (isset($user) && !in_array($questionaire_id, $answeredIds)) ||
            (!isset($user) && $method == 'edit') )
        {
            $questionaire['questions'] = QuestionaireQuestion::with(["options" => function($query) {
                    $query->orderBy('id');
                }])->where('questionaire_id', $questionaire["id"])->get();
            $data = [
                'questionaire' => $questionaire
            ];
            return view($this->templatePath . '.questionaire_survey')
                ->with($data);
        } 

        // if answered already, show answer details.
        // Or if current user is not login and want to view statistic result
        
        $questionaire["questions"] = QuestionaireQuestion::where('questionaire_id', $questionaire["id"])->get()->toArray();
        foreach($questionaire["questions"] as $question_key => $question)
        {
            $questionaire["questions"][$question_key]["options"] = QuestionaireQuestionOption::where('question_id', $question["id"])->select(["option"])->get()->toArray();
        }

        // if questionaire is General, read statistical resutls too.
        if ($questionaire["type"] == "General")
        {
            foreach($questionaire["questions"] as $question_key => $question)
            {
                if ($question["answer_type"] == 'triangle')
                {
                    $answers = QuestionaireAnswer::where('question_id', $question["id"])->select(["answer"])->get()->toArray();
                    $questionaire["questions"][$question_key]["answers"] = [];
                    foreach($answers as $answer_key => $answer)
                    {
                        $questionaire["questions"][$question_key]["answers"][] = json_decode($answer["answer"]);
                    }
                }
                else
                {
                    foreach($questionaire["questions"][$question_key]["options"] as $option_key => $option)
                    {
                        $questionaire["questions"][$question_key]["options"][$option_key]["cnt"] = QuestionaireAnswer::where('question_id', $question["id"])->where('answer', $option["option"])->count();
                    }
                }
            }
        }
        
        if(isset($user))
        {
            // Find current user's answer
            $answers = $user->questionaireAnswers()->where('questionaire_id', $questionaire_id)->get()->toArray();
            // foreach($answers as $key => $answer)
            // {
            //     $answers[$key]['question'] = QuestionaireQuestion::with(["options" => function($query) {
            //             $query->orderBy('id');
            //         }])->find($answer->question_id);
            // }
        }
        
        $data = [
            'answers' => isset($answers) ? $answers : [],
            'questionaire' => isset($questionaire) ? $questionaire : []
        ];
        return view($this->templatePath . '.questionaire_detail')
            ->with($data);
    }

    public function addAnswer()
    {
        $data = request()->all();
        $questionaireId = $data["questionaire_id"];
        $answers = $data["answers"];
        $userId = null;
        if(Auth::check())
        {
            $userId = auth()->user()->id;
        }
        else
        {
            $questionaire = Questionaire::find($questionaireId);
            if($questionaire->access_level != 0)
            {
                return "Invalid Request. Guest can't post answer for specific questionaire";
            }
        }

        foreach($answers as $answer)
        {
            $dataInsert = [
                "user_id" => $userId,
                "questionaire_id" => $questionaireId,
                "question_id" => $answer["question_id"],
                "answer" => $answer["answer"]
            ];
            QuestionaireAnswer::create($dataInsert);
        }
        return 'ok';
    }
}

?>