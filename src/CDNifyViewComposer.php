<?php

namespace Metrique\CDNify;

use Illuminate\Contracts\View\View;
use Metrique\CDNify\Contracts\CDNifyRepositoryInterface as CDNifyRepository;

class CDNifyViewComposer
{
    /**
     * The cdnify repository implementation.
     *
     * @var CDNifyRepository
     */
    protected $cdnify;

    /**
     * Create a new profile composer.
     *
     * @param  UserRepository  $users
     * @return void
     */
    public function __construct(CDNifyRepository $cdnify)
    {
        // Dependencies automatically resolved by service container...
        $this->cdnify = $cdnify;
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $view->with('cdnify', $this->cdnify);
    }
}