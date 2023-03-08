<?php

namespace Waka\YamlForms;

interface YamlFormsInterface
{
    /**
     * Définit le rôle de l'utilisateur pour filtrer les champs en fonction des permissions.
     *
     * @param string $role Le nom du rôle de l'utilisateur.
     */
    public static function setRole($role);

    /**
     * Renvoie un tableau de règles de validation Laravel basé sur les champs YAML requis.
     *
     * @return array Un tableau associatif de règles de validation Laravel.
     */
    public static function getValidationRules();

    /**
     * Renvoie un tableau de champs YAML pour être utilisé dans un formulaire HTML.
     *
     * @return array Un tableau associatif de champs YAML avec des propriétés adaptées aux formulaires.
     */
    public static function getFormFields();

    // /**
    //  * Renvoie la valeur qui existe dans valueFrom d'un field.
    //  *
    //  * @return Any une valeur
    //  */
    // public function getFieldValueFrom($fields);
    
    // /**
    //  * Renvoie la valeur qui existe dans valueFrom d'un field.
    //  *
    //  * @return Any une valeur
    //  */
    // public function getColumnValueFrom($fields);

     /**
     * Renvoie toutes les colonnes qui peuvent être recherchées.
     
     * @return Any une valeur
     */
    public static function dataYamlColumnTransformer($data);
}