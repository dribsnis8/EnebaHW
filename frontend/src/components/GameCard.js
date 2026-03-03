import React, { useState } from 'react';
import './GameCard.css';

const GAME_COLORS = {
  'EA SPORTS FIFA 23':     { bg: '#1a6b3c', accent: '#4caf82', label: 'FIFA 23' },
  'Red Dead Redemption 2': { bg: '#6b2020', accent: '#e05050', label: 'RDR 2' },
  'Split Fiction':         { bg: '#1a3a6b', accent: '#6894e0', label: 'Split Fiction' },
};

function GameCover({ name, imageUrl }) {
  const [imgError, setImgError] = useState(false);
  const theme = GAME_COLORS[name] || { bg: '#2a2a3e', accent: '#7b68ee', label: name };

  if (imageUrl && !imgError) {
    return (
      <img
        className="game-card-cover game-card-cover-image"
        src={imageUrl}
        alt={name}
        onError={() => setImgError(true)}
      />
    );
  }
  return (
    <div
      className="game-card-cover"
      style={{ background: `linear-gradient(135deg, ${theme.bg} 0%, #13131f 100%)` }}
    >
      <span className="game-card-cover-label" style={{ color: theme.accent }}>
        {theme.label}
      </span>
    </div>
  );
}

function GameCard({ game }) {
  const discountedPrice = game.discount
    ? (game.price * (1 - game.discount / 100)).toFixed(2)
    : null;

  return (
    <div className="game-card">
      <GameCover name={game.game_name} imageUrl={game.image_url} />
      <div className="game-card-body">
        <h3 className="game-card-title">{game.game_name}</h3>
        <span className="game-card-platform">{game.platform_name}</span>
        <span className="game-card-region">{game.region_name}</span>
        <p className="game-card-details">{game.details}</p>
        <div className="game-card-pricing">
          {game.discount ? (
            <>
              <span className="game-card-original-price">${game.price.toFixed(2)}</span>
              <span className="game-card-discount-badge">-{game.discount}%</span>
              <span className="game-card-price">${discountedPrice}</span>
            </>
          ) : (
            <span className="game-card-price">${game.price.toFixed(2)}</span>
          )}
        </div>
      </div>
    </div>
  );
}

export default GameCard;
