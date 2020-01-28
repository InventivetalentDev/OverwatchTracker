CREATE TABLE `overwatch_levels` (
  `name` varchar(20) NOT NULL,
  `prestige` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  `date` date NOT NULL
)
ALTER TABLE `overwatch_levels`
  ADD UNIQUE KEY `name` (`name`,`date`);

CREATE TABLE `overwatch_ratings` (
  `name` varchar(20) NOT NULL,
  `role` varchar(10) NOT NULL,
  `rating` int(11) NOT NULL,
  `date` date NOT NULL
)
ALTER TABLE `overwatch_ratings`
  ADD UNIQUE KEY `name_2` (`name`,`role`,`date`);



-- Insert the players you want to track here (You can get the current real data from https://ow-api.com/)
-- The overwatch_levels table is used to query the names to track
INSERT INTO `overwatch_levels` (`name`, `prestige`, `level`, `date`) VALUES
('Yeleha#2188', 11, 26, '2020-01-20')
