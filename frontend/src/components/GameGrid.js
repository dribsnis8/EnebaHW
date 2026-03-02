import React from 'react';
import GameCard from './GameCard';
import './GameGrid.css';

function GameGrid({ games }) {
  if (!games || games.length === 0) {
    return <p className="no-results">No games found.</p>;
  }

  return (
    <div className="game-grid">
      {games.map(game => (
        <GameCard key={game.game_id} game={game} />
      ))}
    </div>
  );
}

export default GameGrid;
