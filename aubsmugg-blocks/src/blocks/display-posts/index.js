/**
 * Registers a new block provided a unique name and an object defining its behavior.
 */
 import { registerBlockType } from '@wordpress/blocks';
 
 /**
  * Internal dependencies
  */
 import edit from './edit';
 
 /**
  * Every block starts by registering a new block type definition.
  */
 registerBlockType('aubsmugg/display-posts', {
    
     /**
      * wp-admin display
      */
     edit: edit,

     /**
	 * front-end display
	 */
     save: () => null,

 });