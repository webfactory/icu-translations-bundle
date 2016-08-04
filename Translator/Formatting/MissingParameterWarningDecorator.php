<?php

namespace Webfactory\IcuTranslationBundle\Translator\Formatting;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Webfactory\IcuTranslationBundle\Translator\Formatting\Exception\FormattingException;

/**
 * Decorator that generates a warning log entry whenever a parameter for a formatted message seems to be missing.
 *
 * As missing parameters are simply ignored, these kind of mistakes can lead to serious debugging effort.
 * This formatter decorator tries to find these error spots early.
 */
class MissingParameterWarningDecorator extends AbstractFormatterDecorator
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Creates a decorator for the provided formatter.
     *
     * @param \Webfactory\IcuTranslationBundle\Translator\Formatting\FormatterInterface $innerFormatter
     * @param LoggerInterface $logger
     */
    public function __construct(FormatterInterface $innerFormatter, LoggerInterface $logger = null)
    {
        parent::__construct($innerFormatter);
        $this->logger = ($logger !== null) ? $logger : new NullLogger();
    }

    /**
     * Checks if all mentioned parameters are provided.
     *
     * @param string $locale
     * @param string $message
     * @param array(string=>mixed) $parameters
     * @return string The formatted message.
     */
    public function format($locale, $message, array $parameters)
    {
        $pattern = '/\{(?P<variables>[a-zA-Z0-9_]+)/u';
        preg_match_all($pattern, $message, $matches,  PREG_PATTERN_ORDER);
        $usedParameters = $matches['variables'];
        $availableParameters = array_keys($parameters);
        $missingParameters = array_diff($usedParameters, $availableParameters);
        if (count($missingParameters) > 0) {
            $logMessage = 'The parameters %s are probably missing in the message "%s".';
            $logMessage = sprintf($logMessage, implode(',', $missingParameters), $message);
            $this->logger->error(
                $logMessage,
                array(
                    'locale' => $locale,
                    'message' => $message,
                    'parameters' => $parameters,
                    // Add an exception (but do not throw it) to ensure that we get a stack trace.
                    'exception' => new FormattingException($logMessage)
                )
            );
        }
        return parent::format($locale, $message, $parameters);
    }
}
