<?php

namespace Zeus\Email;

/**
 *
 */
interface EmailFactoryInterface
{
    public function build(EmailBuilder $builder);
}
