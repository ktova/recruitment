<?php

/*
 * Infomaniak Recruitement Exercise
 * Expand Controller Class
 * Author : Teva Keo
 * Author URI : https://tova.dev
 */

require __DIR__ . '/../vendor/autoload.php';

class ExpandController
{
    private $requestMethod;
    private $dataTree = [];

    /**
     * Constructor
     * @param string $requestMethod - Must be POST for our case.
     */
    public function __construct( $requestMethod )
    {
        $this->requestMethod = $requestMethod;
    }

    /**
     * Process our request from the endpoint
     * @param array $body - The POST Request's body content.
     */
    public function processRequest( $body )
    {
        switch ( $this->requestMethod ) {

            case 'POST':

                // Variables init
                $bodyData = json_decode( $body );
                $index_key = '';

                // Create Tree
                foreach ( $bodyData as $item => $validators ):

                    // Explode node keys to check if array type
                    $is_array = $validator_args = false;
                    $item_keys =  explode( '.', $item );
                    $validator = $validator_type = $validator_arg = array();
                    $validators = '';

                    if( in_array( '*', $item_keys ) ) {
                        $is_array = true;
                        unset( $item_keys[1] );
                    }
                    $item_keys = array_reverse( $item_keys );

                    // Check node's validator(s)
                    if ( strpos( $validators, '|' ) !== false ):
                        $validator = explode( '|', $validators );
                        $validator_type = $validator[0];

                        // Check if validator has multiple options
                        if ( strpos( $validator[1], ':' ) !== false ):
                            $validator_arg = explode( ':',$validator[1] );
                            // arg 0 is key :: arg 1 is value
                        endif;

                    else:
                        $validator_type = $validators;

                    endif;
                    
                   // Loop through item keys
                    if( count( $item_keys ) == 1 ):

                        $is_array ? $type = 'array' : $type = 'object';
                        $validator_type = [ "validator" => [ [ $type ] ] ];
                        $this->create_dataNode( $item_keys[0], 0, $type, array( $type )) ;

                    else:
                        // Reset is_leaf index :: First treated node is a leaf
                        $is_leaf = true;

                        for( $i = count($item_keys) ; $i >= 1 ; $i-- ):
                
                            // parent node
                            if( $i == 1 ):
                                $is_array ? $type = 'array' : $type = 'object';
                                $this->create_dataNode( $item_keys[0], 0, $type, array( $type ) );
                            
                            // from last child to first child
                            else :

                                // leaves exceptions
                                $is_leaf ? $type = 'leaf' : $type = 'object';
                                $is_leaf ? $validator_value = array( $validator_type ) : $validator_value = array( 'object' );
                                
                                ( $is_leaf && $validator_arg[0] === 'keys' ) ? $keyobj_args = true : $keyobj_args = false;
                                ( $is_leaf && !empty($validator_arg[0]) ) ? array_push( $validator_value, $validator[1] ) : ( $validator_value );

                                $is_leaf = false;

                                // If a node has a keys specified validator
                                if( $keyobj_args ):
                                    
                                    // Generate leaves for the last node
                                    foreach( explode( ',', $validator_arg[1] ) as $keyobj ):
                                        $this->create_dataNode( $keyobj, $item_keys[0], 'leaf', array() );
                                    endforeach;

                                    // Generate the parent
                                    $this->create_dataNode( $item_keys[0], $item_keys[1], 'object', array('object') );

                                    unset($item_keys[0]);
                                    $item_keys = array_values( $item_keys );

                                    // Reset keyobj values
                                    $keyobj_args = false;
                                    $validator_arg = array();

                                else:
                                    $this->create_dataNode( $item_keys[0], $item_keys[1], $type, $validator_value );

                                    unset( $item_keys[0] );
                                    unset( $validator_arg[0] );
                                    $item_keys = array_values( $item_keys );

                                endif;

                            endif;
                                
                        endfor;
                    endif;

                endforeach;

                // Clear dataTree from duplicates
                $this->dataTree = array_map( "unserialize", array_unique( array_map( "serialize", $this->dataTree ) ) );
                $this->dataTree = array_values( $this->dataTree );

                // DEBUG
                $processTree = array();
                foreach ( $this->dataTree as $dataTree_item ){
                    $processTree[$dataTree_item['parentId']][] = $dataTree_item;
                }
                
                // Create the Tree element and remove unwanted data
                $tree = $this->createTree( $processTree, $processTree[0] );
                $tree = $this->remove_ids( $tree );

                // Return response
                $response = $this->expandRequest( $tree );
                break;

            default:
                $response = $this->invalidEndpoint();
                break;

        }

        // Display Response
        header( $response['status_code_header'] );
        if ( $response['body'] ) 
        {
            echo $response['body'];
        }
    }

    // Tree functions
    /**
     * Generate and add a node to the $dataTree variable
     * @param string $nodeId - ID of the submitted node
     * @param string $parentId - ID of the node's parent
     * @param string $type - Node's type
     * @param array $validators - Node's validators
     */
    private function create_dataNode($nodeId, $parentId, $type, $validators)
    {
        $temp = [];
        $temp = [
            'nodeId' => $nodeId, 
            'parentId' => $parentId, 
            'type' => $type, 
            'validators' => $validators,
        ];

        array_push( $this->dataTree, $temp );
    }

    /**
     * Generate the response Tree from the submitted data Array
     * @param array $list - a list of tree nodes; a node is an array with keys: id, parentID, name.
     */
    private function createTree(&$list, $parent)
    {
        $tree = array();
        foreach ( $parent as $key=>$value ){

            if( isset( $list[$value['nodeId']] ) )
            {
                // Generate a different Tree for array objects
                if( $value['type'] == 'array') {
                    $value['items']['type'] = 'object';
                    $value['items']['validators'] = array('object');
                    $value['items']['properties'] = $this->createTree( $list, $list[$value['nodeId']] );
                } else {
                    $value['properties'] = $this->createTree( $list, $list[$value['nodeId']] );
                }
            }

            $tree[$value['nodeId']] = $value;
        } 
        return $tree;
    }

    /**
     * Recursively unset values based on their key (parentId and nodeId)
     * @param array $tree - a tree where node_id and parent_id have to be removed;
     */
    private function remove_ids(&$tree)
    {
        unset( $tree['nodeId'] );
        unset( $tree['parentId'] );
        foreach ( $tree as &$value ) {
            if ( is_array($value) ) {
                $this->remove_ids( $value );
            }
        }

        return $tree;
    }
    
    // API Functions
    /*
     * User tried to reach an invalid endpoint
     */
    private function invalidEndpoint()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = json_encode([
            'error' => 'Invalid Endpoint.',
        ]);
        return $response;
    }

    /**
     * Display the response object on the user's end
     * @param array $data - the response tree object
     */
    private function expandRequest($data)
    {
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($data);
        return $response;
    }
}