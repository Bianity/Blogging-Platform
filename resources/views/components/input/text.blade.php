@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'block w-full dark:bg-secondary-700 dark:text-white placeholder-secondary-500 dark:placeholder-secondary-400 rounded-md shadow-sm border-secondary-300 dark:border-secondary-700 focus:ring-primary-500 focus:border-primary-500 sm:text-sm']) !!} />
