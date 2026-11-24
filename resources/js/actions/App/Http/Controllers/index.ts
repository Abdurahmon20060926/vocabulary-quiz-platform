import AuthController from './AuthController'
import ExploreController from './ExploreController'
import QuizController from './QuizController'
import RunController from './RunController'
import PlaysController from './PlaysController'
import StudioController from './StudioController'

const Controllers = {
    AuthController: Object.assign(AuthController, AuthController),
    ExploreController: Object.assign(ExploreController, ExploreController),
    QuizController: Object.assign(QuizController, QuizController),
    RunController: Object.assign(RunController, RunController),
    PlaysController: Object.assign(PlaysController, PlaysController),
    StudioController: Object.assign(StudioController, StudioController),
}

export default Controllers