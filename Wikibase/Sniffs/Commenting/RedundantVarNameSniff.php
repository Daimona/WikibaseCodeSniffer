<?php

namespace Wikibase\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Custom sniff that reports and repairs "@var" documentations of class properties that repeat the
 * variable name, which is unnecessary.
 *
 * @since 0.2.0
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class RedundantVarNameSniff implements Sniff {

	public function register() {
		return [ T_DOC_COMMENT_TAG ];
	}

	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( strcasecmp( $tokens[$stackPtr]['content'], '@var' ) !== 0 ) {
			return;
		}

		$docPtr = $phpcsFile->findNext( T_DOC_COMMENT_WHITESPACE, $stackPtr + 1, null, true );
		if ( !$docPtr || $tokens[$docPtr]['code'] !== T_DOC_COMMENT_STRING ) {
			return;
		}

		$variablePtr = $phpcsFile->findNext( T_VARIABLE, $stackPtr + 1 );
		if ( !$variablePtr ) {
			return;
		}

		$visibilityPtr = $phpcsFile->findPrevious(
			T_WHITESPACE,
			$variablePtr - 1,
			$stackPtr + 1,
			true
		);
		if ( !in_array( $tokens[$visibilityPtr]['code'], [ T_PRIVATE, T_PROTECTED, T_PUBLIC ] ) ) {
			return;
		}

		$variableName = $tokens[$variablePtr]['content'];

		if ( preg_match(
				'/^(\S+\s)?\s*' . preg_quote( $variableName, '/' ) . '\b\s*(.*)/is',
				$tokens[$docPtr]['content'],
				$matches
			)
			&& $phpcsFile->addFixableError(
				"Found redundant variable name \"$variableName\" in @var",
				$docPtr,
				'Found'
			)
		) {
			$phpcsFile->fixer->replaceToken( $docPtr, trim( $matches[1] . $matches[2] ) );
		}
	}

}
