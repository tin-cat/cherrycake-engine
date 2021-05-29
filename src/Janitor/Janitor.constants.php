<?php

namespace Cherrycake\Janitor;

// Janitor
const JANITORTASK_EXECUTION_RETURN_WARNING = 0; // Return code for JanitorTask run when task returned a warning.
const JANITORTASK_EXECUTION_RETURN_ERROR = 1; // Return code for JanitorTask run when task returned an error.
const JANITORTASK_EXECUTION_RETURN_CRITICAL = 2; // Return code for JanitorTask run when task returned a critical error.
const JANITORTASK_EXECUTION_RETURN_OK = 3; // Return code for JanitorTask run when task was executed without errors.

const JANITORTASK_EXECUTION_PERIODICITY_ONLY_MANUAL = 0; // The task can only be executed when calling the Janitor run process with an specific task parameter.
const JANITORTASK_EXECUTION_PERIODICITY_ALWAYS = 1; // The task will be executed every time Janitor run is called.
const JANITORTASK_EXECUTION_PERIODICITY_EACH_SECONDS = 2; // The task will be executed every specified seconds. Seconds are specified in "periodicityEachSeconds" config key.
const JANITORTASK_EXECUTION_PERIODICITY_MINUTES = 3; // The task will be executed on the given minutes of each hour. Desired minutes are specified as an array in the "periodicityMinutes" config key. For example: [0, 15, 30, 45]
const JANITORTASK_EXECUTION_PERIODICITY_HOURS = 4; // The task will be executed on the given hours of each day. Desired hours/minute are specified as an array in the "periodicityHours" config key in the syntax ["hour:minute", ...] For example: ["00:00", "10:45", "20:15"]
const JANITORTASK_EXECUTION_PERIODICITY_DAYSOFMONTH = 5; // The task will be executed on the given days of each month. Desired days/hour/minute are specified as an array in the "periodicityDaysOfMonth" config key in the syntax ["day@hour:minute", ...] For example: ["1@12:00", "15@18:30", "20@00:00"] (Take into account days of month that do not exist)
