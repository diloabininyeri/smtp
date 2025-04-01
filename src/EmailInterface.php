<?php

namespace Zeus\Email;

interface EmailInterface
{
    public function build(EmailBuilder $builder);
}
